<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use PHPUnit\Framework\TestCase;

class RequestsTest extends TestCase
{
    public function testUris(): void
    {
        $uris = [
            'app://self/like?comment_id=1',
            'app://self/like?comment_id=2',
            'app://self/like?comment_id=3',
        ];
        $requests = new Requests($uris);

        $this->assertSame($uris, $requests->uris());
    }

    public function testGetQueryParam(): void
    {
        $uris = [
            'app://self/like?comment_id=10',
            'app://self/like?comment_id=20',
            'app://self/like?comment_id=30',
        ];
        $requests = new Requests($uris);

        $this->assertSame(['10', '20', '30'], $requests->getQueryParam('comment_id'));
    }

    public function testGetQueryParamMissing(): void
    {
        $uris = [
            'app://self/like?comment_id=10',
            'app://self/like?other=20',
            'app://self/like?comment_id=30',
        ];
        $requests = new Requests($uris);

        $this->assertSame(['10', '30'], $requests->getQueryParam('comment_id'));
    }

    public function testGroupBy(): void
    {
        $uris = [
            'app://self/like?comment_id=10',
            'app://self/like?comment_id=20',
            'app://self/like?comment_id=10',
        ];
        $requests = new Requests($uris);

        $expected = [
            '10' => [
                'app://self/like?comment_id=10',
                'app://self/like?comment_id=10',
            ],
            '20' => [
                'app://self/like?comment_id=20',
            ],
        ];
        $this->assertSame($expected, $requests->groupBy('comment_id'));
    }

    public function testMapResults(): void
    {
        $uris = [
            'app://self/like?comment_id=10',
            'app://self/like?comment_id=20',
            'app://self/like?comment_id=30',
        ];
        $requests = new Requests($uris);

        $rows = [
            ['id' => 1, 'comment_id' => '10', 'user_id' => 'user1'],
            ['id' => 2, 'comment_id' => '10', 'user_id' => 'user2'],
            ['id' => 3, 'comment_id' => '20', 'user_id' => 'user3'],
            ['id' => 4, 'comment_id' => '30', 'user_id' => 'user4'],
            ['id' => 5, 'comment_id' => '30', 'user_id' => 'user5'],
            ['id' => 6, 'comment_id' => '30', 'user_id' => 'user6'],
        ];

        $results = $requests->mapResults($rows, 'comment_id');

        $this->assertSame(
            [
                ['id' => 1, 'comment_id' => '10', 'user_id' => 'user1'],
                ['id' => 2, 'comment_id' => '10', 'user_id' => 'user2'],
            ],
            $results->get('app://self/like?comment_id=10'),
        );

        $this->assertSame(
            [
                ['id' => 3, 'comment_id' => '20', 'user_id' => 'user3'],
            ],
            $results->get('app://self/like?comment_id=20'),
        );

        $this->assertSame(
            [
                ['id' => 4, 'comment_id' => '30', 'user_id' => 'user4'],
                ['id' => 5, 'comment_id' => '30', 'user_id' => 'user5'],
                ['id' => 6, 'comment_id' => '30', 'user_id' => 'user6'],
            ],
            $results->get('app://self/like?comment_id=30'),
        );
    }

    public function testMapResultsWithEmptyResult(): void
    {
        $uris = [
            'app://self/like?comment_id=10',
            'app://self/like?comment_id=20',
        ];
        $requests = new Requests($uris);

        $rows = [
            ['id' => 1, 'comment_id' => '10', 'user_id' => 'user1'],
        ];

        $results = $requests->mapResults($rows, 'comment_id');

        $this->assertSame(
            [
                ['id' => 1, 'comment_id' => '10', 'user_id' => 'user1'],
            ],
            $results->get('app://self/like?comment_id=10'),
        );

        $this->assertSame([], $results->get('app://self/like?comment_id=20'));
    }
}
