<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_USER') and object.user == user",
            normalizationContext: ['groups' => ['Subscription:V$Detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['Subscription:V$List']]
        ),
    ]
)]
class Subscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public string $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public User $user;

    #[ORM\Column(length: 100)]
    public string $stripeCustomerId;

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $stripeSubscriptionId = null;

    #[ORM\Column(length: 100)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public string $stripePriceId;

    #[ORM\Column(length: 50)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public string $planName; // "the homies", "Digital Nomad"

    #[ORM\Column(length: 20)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public string $billingPeriod; // "monthly", "yearly"

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public string $amount;

    #[ORM\Column(length: 20)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public string $status = 'pending'; // pending, active, canceled, expired, past_due

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['Subscription:V$List', 'Subscription:V$Detail'])]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
