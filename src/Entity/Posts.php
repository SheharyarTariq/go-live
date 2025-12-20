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
use App\Entity\Comment;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`posts`')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['Posts:V$List']],
        ),
        new Get(
            security: "is_granted('ROLE_USER') and object.user_id == user",
            normalizationContext: ['groups' => ['Posts:V$Detail']],
        ),
        new Post(
            processor: \App\State\PostProcessor::class,
            normalizationContext: ['groups' => ['Posts:V$Create']],
            denormalizationContext: ['groups' => ['Posts:W$Create']],
            validationContext: ['groups' => ['Default', 'Valid(Posts:W$Create)']],
        ),
        new Put(
            processor: \App\State\PostUpdateProcessor::class,
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['Posts:V$Update']],
            denormalizationContext: ['groups' => ['Posts:W$Update']],
            validationContext: ['groups' => ['Default', 'Valid(Posts:W$Update)']],
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.user_id == user",
        ),
        new GetCollection(
            uriTemplate: '/users/posts',
            security: "is_granted('ROLE_USER')",
            provider: \App\State\UserPostsProvider::class,
            normalizationContext: ['groups' => ['Posts:V$List']],
        ),

    ],
)]

#[UniqueEntity(
    fields: ['title'],
    message: 'This title already exists. Please choose a different title.',
    groups: ['Valid(Posts:W$Create)', 'Valid(Posts:W$Update)']
)]
class Posts
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    // #[Assert\NotNull(groups: ['Posts:W$Create'])]
    #[Serializer\Groups(['posts:V$user'])]
    public User $user_id;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(groups: ['Valid(Posts:W$Create)'])]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create', 'Posts:V$Update', 'Posts:W$Create',  'Posts:W$Update'])]
    public string $title;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['Valid(Posts:W$Create)'])]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create', 'Posts:V$Update', 'Posts:W$Create',  'Posts:W$Update'])]
    public string $content;

    #[ORM\Column(enumType: PostType::class)]
    #[Assert\NotNull(groups: ['Valid(Posts:W$Create)'])]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create', 'Posts:V$Update', 'Posts:W$Create',  'Posts:W$Update'])]
    public PostType $type;

    #[ORM\Column]
    #[Assert\NotBlank(groups: ['Valid(Posts:W$Create)'])]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create', 'Posts:V$Update', 'Posts:W$Create',  'Posts:W$Update'])]
    public string $media_url;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create', 'Posts:V$Update'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class)]
    #[ApiProperty(readableLink: false, writableLink: false)]
    #[Serializer\Groups(['Comment:V$List'])]
    public Collection $comments;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['Posts:V$Detail', 'Posts:V$List', 'Posts:V$Create', 'Posts:V$Update'])]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->type = PostType::blog;
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
