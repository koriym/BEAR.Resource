<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\User;

use BEAR\Resource\Method;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

class Entry extends ResourceObject
{
    private array $entries = [
        100 => ['id' => 100, 'title' => 'Entry1'],
        101 => ['id' => 101, 'title' => 'Entry2'],
        102 => ['id' => 102, 'title' => 'Entry3'],
    ];

    public function __construct(private readonly ?ResourceInterface $resource = null)
    {
    }

    /**
     * @return array
     */
    public function onGet()
    {
        return $this->entries;
    }

    public function onLinkComment(ResourceObject $ro)
    {
        $request = $this->resource->newRequest(
            Method::GET,
            'app://self/User/Entry/Comment',
            ['entry_id' => $ro->body['id']],
        )->request();

        return $request;
    }
}
