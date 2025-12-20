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

use ApiPlatform\Metadata\Link;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/posts/{postId}/comment',
            uriVariables: [
                'postId' => new Link(fromClass: Posts::class, fromProperty: 'comments')
            ],
            normalizationContext: ['groups' => ['Comment:V$List']],
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: CommentProcessor::class,
            normalizationContext: ['groups' => ['Comment:V$Create']],
            denormalizationContext: ['groups' => ['Comment:W$Create']],
            validationContext: ['groups' => ['Default', 'Valid(Comment:W$Create)']],
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.user == user",
        ),
    ],
)]

class Comment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Serializer\Groups(['Comment:V$Create', 'Comment:V$List'])]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Groups(['Comment:V$Create'])]
    public User $user;

    #[ORM\ManyToOne(targetEntity: Posts::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(groups: ['Valid(Comment:W$Create)'])]
    #[Serializer\Groups(['Comment:V$Create', 'Comment:W$Create'])]
    public Posts $post;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(groups: ['Valid(Comment:W$Create)'])]
    #[Serializer\Groups(['Comment:V$Create', 'Comment:W$Create', 'Comment:V$List'])]
    public string $content;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Serializer\Groups(['Comment:V$Create'])]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
