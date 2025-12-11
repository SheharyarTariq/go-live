<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CheckoutController extends AbstractController
{
    private const PLAN_CONFIGS = [
        'price_1SWezhQ6K2HRa1FnWIjO8Dhz' => [
            'name' => 'the homies',
            'period' => 'monthly',
            'amount' => '9.99'
        ],
        'price_1SWf0WQ6K2HRa1Fnwphjglfr' => [
            'name' => 'the homies',
            'period' => 'yearly',
            'amount' => '99.99'
        ],
        'price_1SWf1BQ6K2HRa1FnEPOqW7be' => [
            'name' => 'Digital Nomad',
            'period' => 'monthly',
            'amount' => '19.99'
        ],
        'price_1SWf1xQ6K2HRa1FnnUkYZQEA' => [
            'name' => 'Digital Nomad',
            'period' => 'yearly',
            'amount' => '199.99'
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $stripeSecretKey
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/api/checkout/create-session', name: 'create_checkout_session', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createCheckoutSession(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $priceId = $data['priceId'] ?? null;

        if (!$priceId || !isset(self::PLAN_CONFIGS[$priceId])) {
            return $this->json(['error' => 'Invalid price ID'], 400);
        }

        $planConfig = self::PLAN_CONFIGS[$priceId];

        try {
            // Create or retrieve Stripe customer
            if (!$user->stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);
                $user->stripeCustomerId = $customer->id;
                $this->entityManager->flush();
            }

            // Create Stripe Checkout Session
            $checkoutSession = StripeSession::create([
                'customer' => $user->stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->getParameter('app.frontend_url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->getParameter('app.frontend_url') . '/subscription/cancel',
                'metadata' => [
                    'user_id' => $user->id,
                    'price_id' => $priceId,
                    'plan_name' => $planConfig['name'],
                    'billing_period' => $planConfig['period'],
                    'amount' => $planConfig['amount']
                ]
            ]);

            return $this->json([
                'sessionId' => $checkoutSession->id,
                'url' => $checkoutSession->url
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/subscription/status', name: 'subscription_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getSubscriptionStatus(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['user' => $user]);

        if (!$subscription) {
            return $this->json([
                'hasSubscription' => false,
                'status' => 'none'
            ]);
        }

        return $this->json([
            'hasSubscription' => true,
            'status' => $subscription->status,
            'planName' => $subscription->planName,
            'billingPeriod' => $subscription->billingPeriod,
            'amount' => $subscription->amount,
            'currentPeriodEnd' => $subscription->currentPeriodEnd?->format('Y-m-d H:i:s'),
            'userActive' => $user->active
        ]);
    }
}
