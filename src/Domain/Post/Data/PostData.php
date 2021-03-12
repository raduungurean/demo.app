<?php

namespace App\Domain\Post\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Data Model.
 */
final class PostData
{
    public ?int $id = null;

    public ?string $title;

    public ?string $body;

    public ?int $createdUserId;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);

        $this->id = $reader->findInt('id');
        $this->title = $reader->findString('title');
        $this->body = $reader->findString('body');
        $this->createdUserId = $reader->findInt('created_user_id');
    }

    /**
     * Convert to array.
     *
     * @param array $items The items
     *
     * @return PostData[] The list of posts
     */
    public static function toList(array $items): array
    {
        $posts = [];

        foreach ($items as $data) {
            $posts[] = new PostData($data);
        }

        return $posts;
    }
}
