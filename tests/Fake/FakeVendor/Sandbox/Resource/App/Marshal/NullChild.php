<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Marshal;

use BEAR\Resource\ResourceObject;

/**
 * Resource that returns null body
 */
class NullChild extends ResourceObject
{
    public function onGet(int $id): static
    {
        $this->body = null;

        return $this;
    }
}
