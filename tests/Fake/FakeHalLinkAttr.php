<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Annotation\Link;

class FakeHalLinkAttr extends ResourceObject
{
    #[Link(rel: 'edit', href: '/edit', method: 'put', title: 'Edit this')]
    #[Link(rel: 'profile', href: '/profile')]
    public function onGet(): static
    {
        $this->body = ['one' => 1];

        return $this;
    }
}
