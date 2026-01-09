<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Batch;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use FakeVendor\Sandbox\BatchResolver\LikeBatchResolver;

class Comment extends ResourceObject
{
    /** @var array<int, array<int, array{id: int, article_id: int, body: string}>> */
    private static array $data = [
        1 => [
            ['id' => 10, 'article_id' => 1, 'body' => 'Comment 10'],
            ['id' => 11, 'article_id' => 1, 'body' => 'Comment 11'],
            ['id' => 12, 'article_id' => 1, 'body' => 'Comment 12'],
        ],
        2 => [
            ['id' => 20, 'article_id' => 2, 'body' => 'Comment 20'],
        ],
    ];

    #[Link(crawl: 'comment-tree', rel: 'like', href: 'app://self/batch/like?comment_id={id}', batch: LikeBatchResolver::class)]
    public function onGet(int $article_id = 0): static
    {
        $this->body = self::$data[$article_id] ?? [];

        return $this;
    }
}
