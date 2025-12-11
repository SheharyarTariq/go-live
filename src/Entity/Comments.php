<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\CommentProcessor;
use App\Entity\User;
use App\Entity\Posts;
use Dom\Comment;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use ApiPlatform\Metadata\Link;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`comments`')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/posts/{postId}/comments',
            uriVariables: [
                'postId' => new Link(fromClass: Posts::class, fromProperty: 'comments')
            ],
            normalizationContext: ['groups' => ['comment:V$comments']],
        ),
        new Post(
            uriTemplate: '/comment',
            security: "is_granted('ROLE_USER')",
            processor: CommentProcessor::class,
            normalizationContext: ['groups' => ['comment:V$comment']],
            denormalizationContext: ['groups' => ['comment:W$comment']],
            validationContext: ['groups' => ['comment:write']],
        ),
        new Delete(
            uriTemplate: '/comment/{id}',
            security: "is_granted('ROLE_USER') and object.user == user",
        ),
    ],
)]

class Comments
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['comment:V$comment', 'comment:V$comments'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Groups(['comment:V$comment'])]
    public User $user;

    #[ORM\ManyToOne(targetEntity: Posts::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(groups: ['comment:write'])]
    #[Serializer\Groups(['comment:V$comment', 'comment:W$comment'])]
    public Posts $post;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(groups: ['comment:write'])]
    #[Serializer\Groups(['comment:V$comment', 'comment:W$comment', 'comment:V$comments'])]
    public string $content;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['comment:V$comment'])]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
