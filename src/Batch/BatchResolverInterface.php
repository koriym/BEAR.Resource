<?php

declare(strict_types=1);

namespace BEAR\Resource\Batch;

/**
 * Interface for batch resolvers that process multiple URIs efficiently
 *
 * Implementations should:
 * 1. Extract query parameters from URIs using Requests::getQueryParam()
 * 2. Execute a batch query (e.g., SELECT ... WHERE id IN (...))
 * 3. Map results back to URIs using Requests::mapResults()
 *
 * Example:
 * ```php
 * class LikeBatchResolver implements BatchResolverInterface
 * {
 *     public function __invoke(Requests $requests): Results
 *     {
 *         $ids = $requests->getQueryParam('comment_id');
 *         $rows = $this->fetchByIds($ids);
 *
 *         return $requests->mapResults($rows, 'comment_id');
 *     }
 * }
 * ```
 */
interface BatchResolverInterface
{
    public function __invoke(Requests $requests): Results;
}
