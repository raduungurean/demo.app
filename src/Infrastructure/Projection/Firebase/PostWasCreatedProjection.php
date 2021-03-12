<?php

namespace App\Infrastructure\Projection\Firebase;

use App\Domain\Post\Event\PostWasAdded;
use App\Domain\Post\Repository\PostRepositoryInterface;
use League\Event\Listener;
use Psr\Container\ContainerInterface;

/**
 * Class UpdateReadModel
 * @package App\Domain\Post\Listener
 */
class PostWasCreatedProjection implements Listener
{
    /**
     * @var PostRepositoryInterface
     */
    private PostRepositoryInterface $repository;
    private object $firebaseDb;

    /**
     * UpdateReadModel constructor.
     * @param PostRepositoryInterface $repository The repository
     * @param ContainerInterface $ci The dependency injection container
     */
    public function __construct(
        PostRepositoryInterface $repository,
        ContainerInterface $ci
    ) {
        $this->repository = $repository;
        $this->firebaseDb = $ci->get('Firestore')->database();
    }

    /**
     * @param PostWasAdded $event The event
     */
    public function __invoke($event): void
    {
        if (!$event->postId) {
            return;
        }
        $post = (array) $this->repository->getPostById($event->postId);
        $post['uid'] = (string) $post['createdUserId'];
        unset($post['createdUserId']);
        $collectionReference = $this->firebaseDb->collection('posts');
        $docRef = $collectionReference->document($post['id']);
        $docRef->set($post);
    }
}
