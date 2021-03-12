<?php

namespace App\Domain\Post\Event;

/**
 * Class PostWasAdded
 * @package App\Domain\Post\Event
 */
class PostWasAdded
{
    /**
     * @var int The post Id
     */
    public int $postId;

    /**
     * PostWasAdded constructor.
     * @param int $postId The post id
     */
    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }
}
