<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\Page;

use BEAR\Resource\Request;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

class FakeLoop extends ResourceObject
{
    public function __construct(private readonly ResourceInterface $resource)
    {
    }

    public function onGet(): ResourceObject
    {
        $request = $this->resource->createRequest(Request::GET, '/fake-loop-item');
        foreach (range(1, 5) as $i) {
            $this->body[(string) $i] = $request(['num' => (string) $i]);
        }

        return $this;
    }
}
