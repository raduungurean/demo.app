<?php

namespace App\Domain\Post\Repository;

use App\Domain\Post\Data\PostData;

interface PostRepositoryInterface
{
    /**
     * @param PostData $post The post data
     * @return mixed
     */
    public function insertPost(PostData $post);

    /**
     * @param int $postId The post id
     * @return PostData
     */
    public function getPostById(int $postId): PostData;
}
