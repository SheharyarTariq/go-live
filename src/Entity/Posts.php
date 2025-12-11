<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Enum\PostType;
use App\Entity\User;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiSubresource;
use Doctrine\Common\Collections\Collection;
use App\Entity\Comments;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`posts`')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/posts',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['posts:V$post']],
        ),
        new Get(
            uriTemplate: '/posts/{id}',
            security: "is_granted('ROLE_USER') and object.user_id == user",
            normalizationContext: ['groups' => ['posts:V$post']],
        ),
        new Post(
            uriTemplate: '/posts',
            processor: \App\State\PostProcessor::class,
            normalizationContext: ['groups' => ['posts:V$post']],
            denormalizationContext: ['groups' => ['posts:W$post']],
            validationContext: ['groups' => ['posts:W$post']],
        ),
        new Put(
            uriTemplate: '/posts/{id}',
            processor: \App\State\PostProcessor::class,
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['posts:V$post']],
            denormalizationContext: ['groups' => ['posts:W$post']],
            validationContext: ['groups' => ['posts:W$post']],
        ),
        new Delete(
            uriTemplate: '/posts/{id}',
            security: "is_granted('ROLE_USER') and object.user_id == user",
            denormalizationContext: ['groups' => ['posts:delete']],
        ),
        new GetCollection(
            uriTemplate: '/users/posts',
            security: "is_granted('ROLE_USER')",
            provider: \App\State\UserPostsProvider::class,
            normalizationContext: ['groups' => ['posts:V$post']],
        ),

    ],
)]

#[UniqueEntity(
    fields: ['title'],
    message: 'This title already exists. Please choose a different title.',
    groups: ['posts:W$post']
)]
class Posts
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['posts:V$post'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    // #[Assert\NotNull(groups: ['posts:W$post'])]
    #[Serializer\Groups(['posts:V$user'])]
    public User $user_id;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(groups: ['posts:W$post'])]
    #[Serializer\Groups(['posts:V$post', 'posts:W$post'])]
    public string $title;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['posts:W$post'])]
    #[Serializer\Groups(['posts:V$post', 'posts:W$post'])]
    public string $content;

    #[ORM\Column(enumType: PostType::class)]
    #[Assert\NotNull(groups: ['posts:W$post'])]
    #[Serializer\Groups(['posts:V$post', 'posts:W$post'])]
    public PostType $type;

    #[ORM\Column]
    #[Assert\NotBlank(groups: ['posts:W$post'])]
    #[Serializer\Groups(['posts:V$post', 'posts:W$post'])]
    public string $media_url;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['posts:V$post'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comments::class)]
    #[ApiProperty(readableLink: false, writableLink: false)]
    #[Serializer\Groups(['comment:V$comment'])]
    public Collection $comments;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['posts:V$post'])]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->type = PostType::BLOG;
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
