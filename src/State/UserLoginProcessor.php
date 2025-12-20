<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Webmozart\Assert\Assert;
/**
 * @implements ProcessorInterface<User, JsonResponse>
 */
class UserLoginProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {

        Assert::isInstanceOf($data, User::class, 'Expected User entity');
        $email = $data->email;
        $plainPassword = $data->plainPassword;

        Assert::notEmpty($email, 'Email is required');
        Assert::notEmpty($plainPassword, 'Password is required');

        // Find user by email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $plainPassword)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Return token and user data as JSON response
        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'active' => $user->active,
                'subscription' => $user->subscription,
                'subscriptionEnd' => $user->subscriptionEnd?->format('Y-m-d H:i:s'),
                'createdAt' => $user->createdAt->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
