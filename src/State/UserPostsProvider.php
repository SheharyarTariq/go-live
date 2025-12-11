<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Posts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserPostsProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Get the currently authenticated user
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            throw new AccessDeniedHttpException('You must be authenticated to access this resource');
        }

        // Fetch posts for the authenticated user
        $posts = $this->entityManager->getRepository(Posts::class)
            ->createQueryBuilder('p')
            ->where('p.user_id = :user')
            ->setParameter('user', $currentUser)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $posts;
    }
}
