<?php

declare(strict_types=1);

namespace App\ApiResource\DTO;

final class SubscriptionStatusOutput
{
    public bool $hasSubscription;
    public string $status;
    public ?string $planName = null;
    public ?string $billingPeriod = null;
    public ?string $amount = null;
    public ?string $currentPeriodEnd = null;
    public ?string $userActive = null;
}
