<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\DataLoader;

use BEAR\Resource\DataLoader\DataLoaderInterface;
use BEAR\Resource\DataLoader\Requests;
use BEAR\Resource\DataLoader\Results;
use FakeVendor\Sandbox\Resource\App\Batch\Like;

use function array_merge;

class LikeDataLoader implements DataLoaderInterface
{
    /** @var int Track how many times this loader was called */
    public static int $callCount = 0;

    public function __invoke(Requests $requests): Results
    {
        self::$callCount++;

        // Get all comment IDs from URIs
        $commentIds = $requests->getQueryParam('comment_id');

        // Simulate bulk fetch: SELECT * FROM likes WHERE comment_id IN (...)
        $rows = [];
        foreach ($commentIds as $commentId) {
            $likes = Like::$data[(int) $commentId] ?? [];
            $rows = array_merge($rows, $likes);
        }

        // Map results back to URIs
        return $requests->mapResults($rows, 'comment_id');
    }

    public static function reset(): void
    {
        self::$callCount = 0;
    }
}
