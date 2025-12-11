<?php

declare(strict_types=1);

namespace App\ApiResource\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutSessionInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    public string $priceId;
}
