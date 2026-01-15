<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\Resource\App\Batch;

use BEAR\Resource\ResourceObject;

class Like extends ResourceObject
{
    /** @var array<int, array<int, array{id: int, comment_id: int, user_id: string}>> */
    public static array $data = [
        10 => [
            ['id' => 100, 'comment_id' => 10, 'user_id' => 'user1'],
            ['id' => 101, 'comment_id' => 10, 'user_id' => 'user2'],
        ],
        11 => [
            ['id' => 110, 'comment_id' => 11, 'user_id' => 'user3'],
        ],
        12 => [
            ['id' => 120, 'comment_id' => 12, 'user_id' => 'user4'],
            ['id' => 121, 'comment_id' => 12, 'user_id' => 'user5'],
            ['id' => 122, 'comment_id' => 12, 'user_id' => 'user6'],
        ],
        20 => [
            ['id' => 200, 'comment_id' => 20, 'user_id' => 'user7'],
        ],
    ];

    public function onGet(int $comment_id = 0): static
    {
        $this->body = self::$data[$comment_id] ?? [];

        return $this;
    }
}
