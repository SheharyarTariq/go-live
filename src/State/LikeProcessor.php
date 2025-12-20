<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Like;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

class LikeProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        Assert::isInstanceOf($data, Like::class, 'Expected Like entity');
        // if ($data instanceof Like) {
            $myUser = $this->security->getUser();

            if ($operation instanceof Post) {
                // For POST (create), set the user to the current authenticated user
                $data->user = $myUser;
            }
        // }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
