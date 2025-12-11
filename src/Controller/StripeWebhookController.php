<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $stripeSecretKey,
        private string $stripeWebhookSecret
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/api/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\Exception $e) {
            return new Response('Webhook signature verification failed', 400);
        }

        // Handle different event types
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
        }

        return new Response('Webhook handled', 200);
    }

    private function handleCheckoutSessionCompleted($session): void
    {
        $userId = $session->metadata->user_id ?? null;
        if (!$userId) {
            return;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return;
        }

        // Get the subscription from Stripe with expanded data
        $stripeSubscriptionId = $session->subscription;
        if (!$stripeSubscriptionId) {
            // For one-time payments or if subscription isn't created yet
            // Still activate the user
            $user->active = 'active';
            $this->entityManager->flush();
            return;
        }

        // Handle both string ID and object
        if (is_object($stripeSubscriptionId)) {
            $stripeSubscriptionId = $stripeSubscriptionId->id;
        }

        $stripeSubscription = \Stripe\Subscription::retrieve([
            'id' => $stripeSubscriptionId,
            'expand' => ['latest_invoice']
        ]);

        // Create or update subscription record
        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['user' => $user]);

        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->user = $user;
            $subscription->stripeCustomerId = is_string($session->customer) ? $session->customer : $session->customer->id;
        }

        $subscription->stripeSubscriptionId = $stripeSubscriptionId;
        $subscription->stripePriceId = $session->metadata->price_id ?? '';
        $subscription->planName = $session->metadata->plan_name ?? 'Unknown';
        $subscription->billingPeriod = $session->metadata->billing_period ?? 'monthly';
        $subscription->amount = $session->metadata->amount ?? '0.00';
        $subscription->status = 'active';

        // Safely handle period dates with fallbacks
        $periodStart = $stripeSubscription->current_period_start ?? time();
        $periodEnd = $stripeSubscription->current_period_end ?? (time() + (30 * 24 * 60 * 60));

        $subscription->currentPeriodStart = new \DateTimeImmutable('@' . $periodStart);
        $subscription->currentPeriodEnd = new \DateTimeImmutable('@' . $periodEnd);
        $subscription->updateTimestamp();

        // Update user status to active
        $user->active = 'active';
        $user->subscription = $subscription->planName . ' (' . $subscription->billingPeriod . ')';
        $user->subscriptionEnd = $subscription->currentPeriodEnd;

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    private function handleSubscriptionUpdated($stripeSubscription): void
    {
        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if (!$subscription) {
            return;
        }

        $subscription->status = $stripeSubscription->status ?? 'active';

        // Safely handle period dates with fallbacks
        $periodStart = $stripeSubscription->current_period_start ?? time();
        $periodEnd = $stripeSubscription->current_period_end ?? (time() + (30 * 24 * 60 * 60));

        $subscription->currentPeriodStart = new \DateTimeImmutable('@' . $periodStart);
        $subscription->currentPeriodEnd = new \DateTimeImmutable('@' . $periodEnd);
        $subscription->updateTimestamp();

        // Update user based on subscription status
        $user = $subscription->user;
        if ($stripeSubscription->status === 'active') {
            $user->active = 'active';
        } elseif (in_array($stripeSubscription->status, ['canceled', 'unpaid', 'past_due'])) {
            $user->active = 'inactive';
        }
        $user->subscriptionEnd = $subscription->currentPeriodEnd;

        $this->entityManager->flush();
    }

    private function handleSubscriptionDeleted($stripeSubscription): void
    {
        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if (!$subscription) {
            return;
        }

        $subscription->status = 'canceled';
        $subscription->updateTimestamp();

        // Update user status
        $user = $subscription->user;
        $user->active = 'inactive';

        $this->entityManager->flush();
    }

    private function handleInvoicePaymentSucceeded($invoice): void
    {
        if (!isset($invoice->subscription)) {
            return;
        }

        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $invoice->subscription]);

        if (!$subscription) {
            return;
        }

        // Ensure user is active
        $user = $subscription->user;
        $user->active = 'active';
        $subscription->status = 'active';
        $subscription->updateTimestamp();

        $this->entityManager->flush();
    }

    private function handleInvoicePaymentFailed($invoice): void
    {
        if (!isset($invoice->subscription)) {
            return;
        }

        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $invoice->subscription]);

        if (!$subscription) {
            return;
        }

        $subscription->status = 'past_due';
        $subscription->updateTimestamp();

        // Mark user as inactive
        $user = $subscription->user;
        $user->active = 'inactive';

        $this->entityManager->flush();
    }
}
