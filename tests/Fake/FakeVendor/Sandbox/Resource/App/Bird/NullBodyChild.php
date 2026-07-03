<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Bird;

use BEAR\Resource\ResourceObject;

class NullBodyChild extends ResourceObject
{
    public function onGet(string $id)
    {
        // Simulates a #[CacheableResponse]/#[DonutCache] cache hit that
        // restored only the view, leaving the body null (non-array).
        $this->body = null;

        return $this;
    }
}
