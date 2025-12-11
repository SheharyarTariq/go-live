<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\GetCollection;
use App\State\UserPasswordHasher;
use App\State\UserRegistrationProcessor;
use App\State\UserLoginProcessor;
use App\State\UserPostsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\ApiResource\DTO\UserOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Posts;


#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: 'email', message: 'Email already exists', groups: ['user:write'])]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_USER') and object == user",
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Post(
            uriTemplate: '/auth/register',
            normalizationContext: ['groups' => ['user:read', 'User:V$Register']],
            denormalizationContext: ['groups' => ['user:write']],
            validationContext: ['groups' => ['user:write']],
            // output: UserOutput::class, //no output when return
            output: false,
            processor: UserRegistrationProcessor::class
        ),
        new Post(
            uriTemplate: '/auth/login',
            denormalizationContext: ['groups' => ['user:login']],
            validationContext: ['groups' => ['user:login']],
            output: false,
            processor: UserLoginProcessor::class
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and object == user",
            denormalizationContext: ['groups' => ['user:update']],
            processor: UserPasswordHasher::class
        ),
    ],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['user:read'])]
    public string $id;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(groups: ['user:write', 'user:login'])]
    #[Assert\Email(groups: ['user:write', 'user:login'])]
    #[Serializer\Groups(['user:read', 'user:write', 'user:login'])]
    public string $email;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['user:write'])]
    #[Serializer\Groups(['user:read', 'user:write'])]
    public string $name;

    #[ORM\Column]
    public string $password;

    #[Assert\NotBlank(groups: ['user:write', 'user:login'])]
    #[Serializer\Groups(['user:write', 'user:login'])]
    public ?string $plainPassword;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['user', 'admin'])]
    #[Serializer\Groups(['user:read'])]
    public string $role = 'user';

    #[ORM\Column(length: 20)]
    #[Serializer\Groups(['user:read'])]
    public string $active = 'pending';

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['user:read'])]
    public ?string $subscription = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['user:read'])]
    public ?\DateTimeImmutable $subscriptionEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['user:read'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'user_id', targetEntity: Posts::class)]
    #[Serializer\Groups(['user:read', 'user:$Vpost'])]
    public Collection $posts;

    public function __construct()
    {
        $this->role = 'user';
        $this->active = 'pending';
        $this->createdAt = new \DateTimeImmutable();
        $this->plainPassword = null;
        $this->stripeCustomerId = null;
        $this->subscription = null;
        $this->subscriptionEnd = null;
        $this->posts = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
