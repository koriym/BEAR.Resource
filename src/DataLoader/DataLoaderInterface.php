<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

/**
 * Interface for data loaders that batch multiple resource requests
 *
 * Implementations receive query parameters and return database rows.
 * The framework handles URI parsing and result distribution.
 *
 * Example:
 * ```php
 * class MetaDataLoader implements DataLoaderInterface
 * {
 *     public function __invoke(array $queries): array
 *     {
 *         $postIds = array_column($queries, 'post_id');
 *         return $this->query->findByPostIds($postIds);
 *     }
 * }
 * ```
 *
 * @psalm-type Query = array<string, string>
 * @psalm-type Row = array<string, mixed>
 */
interface DataLoaderInterface
{
    /**
     * @param list<Query> $queries Query parameters extracted from URIs
     *
     * @return list<Row> Rows that contain the query key columns for matching
     */
    public function __invoke(array $queries): array;
}
