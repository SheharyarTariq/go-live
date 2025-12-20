<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Webmozart\Assert\Assert;
use App\ApiResource\DTO\UserOutput;

/**
 * @implements ProcessorInterface<User, UserOutput>
 */
final class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
    
        Assert::isInstanceOf($data, User::class, 'Expected User entity'); 

        if ($data->plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->plainPassword
            );
            $data->password = $hashedPassword;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $output = new UserOutput();
        $output->id = $data->id;
        $output->email = $data->email;
        $output->name = $data->name;
        return $output;
    }
}
