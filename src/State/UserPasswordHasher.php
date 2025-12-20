<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Webmozart\Assert\Assert;

//user password hasher for reader t1(input) output t2(return also user)

/**
 * @implements ProcessorInterface<User, User>
 */
final class UserPasswordHasher implements ProcessorInterface
{
    public function __construct(
        /** @var ProcessorInterface<User, User> */
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $decorated,
        // private EntityManagerInterface $entityManager,
        // private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Only process User entities for password hashing
        // if (!$data instanceof User) {
        Assert::isInstanceOf($data, User::class, 'Expected User entity');
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        // }

        // Hash the password only if plainPassword is set (user is updating password)
        // if ($data->plainPassword) {
        //     $hashedPassword = $this->passwordHasher->hashPassword(
        //         $data,
        //         $data->plainPassword
        //     );
        //     $data->password = $hashedPassword;
        // }

        // $this->entityManager->persist($data);
        // $this->entityManager->flush();

        // return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
