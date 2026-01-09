<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

/**
 * Interface for data loaders that batch multiple resource requests
 *
 * Implementations receive query parameters and return database rows.
 * The framework handles URI parsing and result distribution.
 *
 * $queries is passed as an array of associative arrays (not just values)
 * to support multiple key parameters:
 *
 * Single key: app://self/meta{?post_id}
 * ```php
 * $postIds = array_column($queries, 'post_id');
 * ```
 *
 * Multiple keys: app://self/translation?post_id={post_id}&locale={locale}
 * ```php
 * foreach ($queries as $q) {
 *     // Use both $q['post_id'] and $q['locale']
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
