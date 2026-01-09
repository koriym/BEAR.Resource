<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use PHPUnit\Framework\TestCase;

class ResultsTest extends TestCase
{
    public function testGet(): void
    {
        $results = new Results([
            'app://self/like?comment_id=10' => [['id' => 1]],
            'app://self/like?comment_id=20' => [['id' => 2]],
        ]);

        $this->assertSame([['id' => 1]], $results->get('app://self/like?comment_id=10'));
        $this->assertSame([['id' => 2]], $results->get('app://self/like?comment_id=20'));
    }

    public function testGetMissing(): void
    {
        $results = new Results([
            'app://self/like?comment_id=10' => [['id' => 1]],
        ]);

        $this->assertNull($results->get('app://self/like?comment_id=999'));
    }

    public function testHas(): void
    {
        $results = new Results([
            'app://self/like?comment_id=10' => [['id' => 1]],
        ]);

        $this->assertTrue($results->has('app://self/like?comment_id=10'));
        $this->assertFalse($results->has('app://self/like?comment_id=999'));
    }

    public function testToArray(): void
    {
        $data = [
            'app://self/like?comment_id=10' => [['id' => 1]],
            'app://self/like?comment_id=20' => [['id' => 2]],
        ];
        $results = new Results($data);

        $this->assertSame($data, $results->toArray());
    }
}
