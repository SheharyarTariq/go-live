<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Posts;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PostProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Posts) {
            $currentUser = $this->security->getUser();

            if ($operation instanceof Post) {
                // For POST (create), set the user_id to the current authenticated user
                $data->user_id = $currentUser;
            } elseif ($operation instanceof Put) {
                // For PUT (update), verify ownership
                // Check if the post already has a user_id set (from database)
                if (isset($data->user_id)) {
                    // Verify that the post belongs to the current user
                    if ($data->user_id !== $currentUser) {
                        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                            'You can only update your own posts.'
                        );
                    }
                } else {
                    // If somehow user_id is not set, set it to current user
                    // This shouldn't normally happen for PUT operations
                    $data->user_id = $currentUser;
                }
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
