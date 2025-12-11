<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscription')]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_USER') and object.user == user",
            normalizationContext: ['groups' => ['subscription:read']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['subscription:read']]
        ),
    ]
)]
class Subscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['subscription:read'])]
    public string $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public User $user;

    #[ORM\Column(length: 100)]
    public string $stripeCustomerId;

    #[ORM\Column(length: 100, nullable: true)]
    public ?string $stripeSubscriptionId = null;

    #[ORM\Column(length: 100)]
    #[Serializer\Groups(['subscription:read'])]
    public string $stripePriceId;

    #[ORM\Column(length: 50)]
    #[Serializer\Groups(['subscription:read'])]
    public string $planName; // "the homies", "Digital Nomad"

    #[ORM\Column(length: 20)]
    #[Serializer\Groups(['subscription:read'])]
    public string $billingPeriod; // "monthly", "yearly"

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Serializer\Groups(['subscription:read'])]
    public string $amount;

    #[ORM\Column(length: 20)]
    #[Serializer\Groups(['subscription:read'])]
    public string $status = 'pending'; // pending, active, canceled, expired, past_due

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['subscription:read'])]
    public ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['subscription:read'])]
    public ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['subscription:read'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['subscription:read'])]
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
