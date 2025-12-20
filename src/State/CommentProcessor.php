<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Comment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

class CommentProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        Assert::isInstanceOf($data, Comment::class, 'Expected Comment entity');
        // if ($data instanceof Comment) {
            $myUser = $this->security->getUser();

            if ($operation instanceof Post) {
                // For POST (create), set the user to the current authenticated user
                $data->user = $myUser;
            }
        // }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
