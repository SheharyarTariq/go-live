<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use webmozart\Assert\Assert;
use App\ApiResource\DTO\UserOutput;

final class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        // if (!$data instanceof User) {
        //     throw new \InvalidArgumentException('Expected User entity');
        // }
        Assert::isInstanceOf($data, User::class, 'Expected User entity');  //same as above through webmozart/assert.   assert mean make sure (validation)
        //above $data is instance of User class
        //composer require phpstan/phpstan



        // Hash the password if plainPassword is set
        if ($data->plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->plainPassword
            );
            $data->password = $hashedPassword;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        // Generate JWT token for the newly registered user
        $token = $this->jwtManager->create($data);

        $output = new UserOutput();
        $output->id = $data->id;
        $output->email = $data->email;
        $output->name = $data->name;
        // Return token and user data as JSON response
        return $output;
    }
}
