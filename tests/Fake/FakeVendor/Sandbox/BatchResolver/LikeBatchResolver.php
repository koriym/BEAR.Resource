<?php

declare(strict_types=1);

namespace FakeVendor\Sandbox\BatchResolver;

use BEAR\Resource\Batch\BatchResolverInterface;
use BEAR\Resource\Batch\Requests;
use BEAR\Resource\Batch\Results;
use FakeVendor\Sandbox\Resource\App\Batch\Like;

use function array_merge;

class LikeBatchResolver implements BatchResolverInterface
{
    /** @var int Track how many times this resolver was called */
    public static int $callCount = 0;

    public function __invoke(Requests $requests): Results
    {
        self::$callCount++;

        // Get all comment IDs from URIs
        $commentIds = $requests->getQueryParam('comment_id');

        // Simulate batch fetch: SELECT * FROM likes WHERE comment_id IN (...)
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
