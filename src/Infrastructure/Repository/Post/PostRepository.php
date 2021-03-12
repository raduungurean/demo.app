<?php

namespace App\Infrastructure\Repository\Post;

use App\Domain\Post\Data\PostData;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Factory\QueryFactory;
use Cake\Chronos\Chronos;
use DomainException;

/**
 * Repository.
 */
final class PostRepository implements PostRepositoryInterface
{
    private QueryFactory $queryFactory;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Insert post row.
     *
     * @param PostData $post The post data
     *
     * @return int The new ID
     */
    public function insertPost(PostData $post): int
    {
        $row = $this->toRow($post);
        $row['created_at'] = Chronos::now()->toDateTimeString();

        return (int)$this->queryFactory->newInsert('posts', $row)
            ->execute()
            ->lastInsertId();
    }

    /**
     * @param int $postId The post id
     * @return PostData
     */
    public function getPostById(int $postId): PostData
    {
        $query = $this->queryFactory->newSelect('posts');
        $query->select(
            [
                'id',
                'title',
                'body',
                'created_at',
                'created_user_id'
            ]
        );

        $query->andWhere(['id' => $postId]);

        $row = $query->execute()->fetch('assoc');

        if (!$row) {
            throw new DomainException(sprintf('Post not found: %s', $postId));
        }

        return new PostData($row);
    }

    /**
     * Convert to array.
     *
     * @param PostData $post The post data
     *
     * @return array The array
     */
    private function toRow(PostData $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'body' => $post->body,
            'created_user_id' => $post->createdUserId,
        ];
    }
}
