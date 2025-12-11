#!/usr/bin/env php
<?php
// Test webhook manually
// Usage: php test_webhook.php cs_test_xxxxx

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

// Set Stripe API key
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Get session ID from command line
$sessionId = $argv[1] ?? null;

if (!$sessionId) {
    echo "Usage: php test_webhook.php <session_id>\n";
    echo "Example: php test_webhook.php cs_test_a1oicmxEzoUlJwLiJxHNVEfBLxdZ0SQf8lpJRhk8jAfkV5QW6FasAW6RJo\n";
    exit(1);
}

echo "Fetching session: $sessionId\n";

try {
    // Retrieve the session
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    echo "Session status: " . $session->status . "\n";
    echo "Payment status: " . $session->payment_status . "\n";

    if ($session->payment_status !== 'paid') {
        echo "Warning: Payment not completed yet!\n";
    }

    // Create a fake webhook event
    $event = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => $session
        ]
    ];

    echo "\nSending webhook to http://localhost:8000/api/webhook/stripe\n";

    // Send to webhook endpoint
    $ch = curl_init('http://localhost:8000/api/webhook/stripe');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Stripe-Signature: test_signature'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Response code: $httpCode\n";

    if ($httpCode === 200) {
        echo "✅ Webhook processed successfully!\n";
    } else {
        echo "❌ Webhook failed!\n";
        echo "Response: $response\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
