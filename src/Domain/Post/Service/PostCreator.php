<?php

namespace App\Domain\Post\Service;

use App\Domain\Post\Data\PostData;
use App\Domain\Post\Event\PostWasAdded;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Factory\LoggerFactory;
use Exception;
use League\Event\EventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class PostCreator
{
    private PostRepositoryInterface $repository;
    private PostValidator $postValidator;
    private LoggerInterface $logger;
    /**
     * @var EventDispatcher
     */
    private EventDispatcher $eventDispatcher;

    /**
     * The constructor.
     *
     * @param PostRepositoryInterface $repository The repository
     * @param PostValidator $postValidator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     * @param EventDispatcher $eventDispatcher The dispatcher
     */
    public function __construct(
        PostRepositoryInterface $repository,
        PostValidator $postValidator,
        LoggerFactory $loggerFactory,
        EventDispatcher $eventDispatcher
    ) {
        $this->repository = $repository;
        $this->postValidator = $postValidator;
        $this->logger = $loggerFactory
            ->addFileHandler('post_creator.log')
            ->createLogger();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create a new post.
     *
     * @param array<mixed> $data The form data
     *
     * @return int The new post ID
     * @throws Exception
     */
    public function createPost(array $data): int
    {
        $this->postValidator->validatePost($data);

        $post = new PostData($data);

        $postId = $this->repository->insertPost($post);

        $this->eventDispatcher->dispatch(new PostWasAdded($postId));

        $this->logger->info(sprintf('Post created successfully: %s', $postId));

        return $postId;
    }
}
