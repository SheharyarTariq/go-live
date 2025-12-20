<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Posts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
/**
 * @implements ProviderInterface<Posts>
 */
class UserPostsProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $myUser = $this->security->getUser();

        if (!$myUser) {
            throw new AccessDeniedHttpException('You must be authenticated to access this resource');
        }

        $posts = $this->entityManager->getRepository(Posts::class)
            ->createQueryBuilder('p')
            ->where('p.user_id = :user')
            ->setParameter('user', $myUser)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $posts;
    }
}
