<?php

namespace App\Command;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-checkout-session',
    description: 'Manually process a Stripe checkout session to activate user subscription',
)]
class ProcessCheckoutSessionCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $stripeSecretKey
    ) {
        parent::__construct();
        Stripe::setApiKey($this->stripeSecretKey);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('session_id', InputArgument::REQUIRED, 'Stripe Checkout Session ID')
            ->setHelp(
                'This command manually processes a Stripe checkout session and activates the user subscription.' . PHP_EOL .
                'Usage: php bin/console app:process-checkout-session cs_test_xxxxx'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sessionId = $input->getArgument('session_id');

        $io->title('Processing Checkout Session');
        $io->info("Session ID: $sessionId");

        try {
            // Retrieve the session from Stripe
            $session = StripeSession::retrieve($sessionId);

            $io->section('Session Details');
            $io->table(
                ['Property', 'Value'],
                [
                    ['Status', $session->status],
                    ['Payment Status', $session->payment_status],
                    ['Customer', $session->customer],
                    ['Subscription', $session->subscription ?? 'N/A'],
                ]
            );

            if ($session->payment_status !== 'paid') {
                $io->warning('Payment has not been completed yet!');
                return Command::FAILURE;
            }

            // Get user ID from metadata
            $userId = $session->metadata->user_id ?? null;
            if (!$userId) {
                $io->error('No user_id found in session metadata!');
                return Command::FAILURE;
            }

            // Find user
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("User with ID $userId not found!");
                return Command::FAILURE;
            }

            $io->info("Found user: {$user->email}");

            // Get the subscription from Stripe
            $stripeSubscriptionId = is_string($session->subscription) ? $session->subscription : $session->subscription->id;
            if (!$stripeSubscriptionId) {
                $io->error('No subscription found in the session!');
                return Command::FAILURE;
            }

            $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscriptionId, ['expand' => ['latest_invoice']]);

            // Create or update subscription record
            $subscription = $this->entityManager
                ->getRepository(Subscription::class)
                ->findOneBy(['user' => $user]);

            if (!$subscription) {
                $subscription = new Subscription();
                $subscription->user = $user;
                $subscription->stripeCustomerId = $session->customer;
                $io->info('Creating new subscription record');
            } else {
                $io->info('Updating existing subscription record');
            }

            $subscription->stripeSubscriptionId = $stripeSubscriptionId;
            $subscription->stripePriceId = $session->metadata->price_id;
            $subscription->planName = $session->metadata->plan_name;
            $subscription->billingPeriod = $session->metadata->billing_period;
            $subscription->amount = $session->metadata->amount;
            $subscription->status = 'active';

            // Get subscription periods
            $currentPeriodStart = $stripeSubscription->current_period_start ?? time();
            $currentPeriodEnd = $stripeSubscription->current_period_end ?? (time() + (30 * 24 * 60 * 60));

            $subscription->currentPeriodStart = new \DateTimeImmutable('@' . $currentPeriodStart);
            $subscription->currentPeriodEnd = new \DateTimeImmutable('@' . $currentPeriodEnd);
            $subscription->updateTimestamp();

            // Update user status to active
            $previousStatus = $user->active;
            $user->active = 'active';
            $user->subscription = $subscription->planName . ' (' . $subscription->billingPeriod . ')';
            $user->subscriptionEnd = $subscription->currentPeriodEnd;

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $io->success('Subscription processed successfully!');

            $io->section('Subscription Details');
            $io->table(
                ['Property', 'Value'],
                [
                    ['User Email', $user->email],
                    ['User Status', "$previousStatus → {$user->active}"],
                    ['Plan', $subscription->planName],
                    ['Billing Period', $subscription->billingPeriod],
                    ['Amount', '$' . $subscription->amount],
                    ['Status', $subscription->status],
                    ['Period Start', $subscription->currentPeriodStart->format('Y-m-d H:i:s')],
                    ['Period End', $subscription->currentPeriodEnd->format('Y-m-d H:i:s')],
                ]
            );

            return Command::SUCCESS;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $io->error('Stripe API Error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
