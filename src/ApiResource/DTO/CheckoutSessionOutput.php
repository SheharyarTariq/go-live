<?php

declare(strict_types=1);

namespace App\ApiResource\DTO;

final class CheckoutSessionOutput
{
    public string $sessionId;
    public string $url;
}
