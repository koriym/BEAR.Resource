<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\DataLoader;

use BEAR\Resource\DataLoader\DataLoaderInterface;
use FakeVendor\Sandbox\Resource\App\Batch\Like;

use function array_column;
use function array_merge;

class LikeDataLoader implements DataLoaderInterface
{
    /** @var int Track how many times this loader was called */
    public static int $callCount = 0;

    public function __invoke(array $queries): array
    {
        self::$callCount++;

        // Get all comment IDs from queries
        $commentIds = array_column($queries, 'comment_id');

        // Simulate bulk fetch: SELECT * FROM likes WHERE comment_id IN (...)
        $rows = [];
        foreach ($commentIds as $commentId) {
            $likes = Like::$data[(int) $commentId] ?? [];
            $rows = array_merge($rows, $likes);
        }

        return $rows;
    }

    public static function reset(): void
    {
        self::$callCount = 0;
    }
}
