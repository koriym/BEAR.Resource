<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Batch;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Article extends ResourceObject
{
    #[Link(crawl: 'comment-tree', rel: 'comment', href: 'app://self/batch/comment?article_id={id}')]
    public function onGet(int $id = 0): static
    {
        $this->body = [
            'id' => $id,
            'title' => 'Article ' . $id,
        ];

        return $this;
    }
}
