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
#[ORM\Table(name: '`likes`')]
#[UniqueEntity(
    fields: ['user', 'post'],
    message: 'You have already liked this post.',
    errorPath: 'post',
)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/like',
            security: "is_granted('ROLE_USER')",
            processor: LikeProcessor::class,
            normalizationContext: ['groups' => ['like:read']],
            denormalizationContext: ['groups' => ['like:write']],
            validationContext: ['groups' => ['like:write']],
        ),

        new Delete(
            uriTemplate: '/dislike/{id}',
            security: "is_granted('ROLE_USER') and object.user == user",
            denormalizationContext: ['groups' => ['like:delete']],
        ),
    ],
)]

class Like
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['like:read'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Groups(['like:read'])]
    public User $user;

    #[ORM\ManyToOne(targetEntity: Posts::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(groups: ['like:write'])]
    #[Serializer\Groups(['like:read', 'like:write'])]
    public Posts $post;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['like:read'])]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
