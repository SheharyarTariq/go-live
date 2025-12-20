<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\LikeProcessor;
use App\Entity\User;
use App\Entity\Posts;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['user', 'post'],
    message: 'You have already liked this post.',
    errorPath: 'post',
)]
#[ApiResource(
    operations: [
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: LikeProcessor::class,
            normalizationContext: ['groups' => ['Like:V$Create']],
            denormalizationContext: ['groups' => ['Like:W$Create']],
            validationContext: ['groups' => ['Default', 'Valid(Like:W$Create)']],
        ),

        new Delete(
            uriTemplate: '/dislike/{id}',
            security: "is_granted('ROLE_USER') and object.user == user",
        ),
    ],
)]

class Like
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['Like:V$Create'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Groups(['Like:V$Create'])]
    public User $user;

    #[ORM\ManyToOne(targetEntity: Posts::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(groups: ['Valid(Like:W$Create)'])]
    #[Serializer\Groups(['Like:V$Create', 'Like:W$Create'])]
    public Posts $post;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['Like:V$Create'])]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
