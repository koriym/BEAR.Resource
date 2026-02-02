<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

class Holder extends ResourceObject
{
    public function __construct(ResourceInterface $resource)
    {
        $resource->get('app://self/author', ['id' => 1]);
    }

    public function onPost()
    {
        return true;
    }
}
