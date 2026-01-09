<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

/**
 * Interface for data loaders that process multiple URIs efficiently
 *
 * Implementations should:
 * 1. Extract query parameters from URIs using Requests::getQueryParam()
 * 2. Execute a bulk query (e.g., SELECT ... WHERE id IN (...))
 * 3. Map results back to URIs using Requests::mapResults()
 *
 * Example:
 * ```php
 * class LikeDataLoader implements DataLoaderInterface
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
interface DataLoaderInterface
{
    public function __invoke(Requests $requests): Results;
}
