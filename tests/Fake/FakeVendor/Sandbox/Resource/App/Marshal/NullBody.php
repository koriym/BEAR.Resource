<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Marshal;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

/**
 * Resource that returns null body for testing crawl with null result
 */
class NullBody extends ResourceObject
{
    #[Link(rel: "child", href: "app://self/marshal/null-child?id={id}", crawl: "tree")]
    public function onGet(int $id): static
    {
        $this->body = ['id' => $id, 'name' => 'test'];

        return $this;
    }
}
