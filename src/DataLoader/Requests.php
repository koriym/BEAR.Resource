<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use function array_map;
use function parse_str;
use function parse_url;

use const PHP_URL_QUERY;

/**
 * Collection of URIs for DataLoader processing with helper methods
 *
 * @psalm-type UriString = string
 */
final class Requests
{
    /** @var list<string> */
    private readonly array $uris;

    /** @param list<string> $uris */
    public function __construct(array $uris)
    {
        $this->uris = $uris;
    }

    /**
     * Get all URIs
     *
     * @return list<string>
     */
    public function uris(): array
    {
        return $this->uris;
    }

    /**
     * Get values of a specific query parameter from all URIs
     *
     * @return list<mixed>
     */
    public function getQueryParam(string $name): array
    {
        $values = [];
        foreach ($this->uris as $uri) {
            $query = $this->parseQuery($uri);
            if (isset($query[$name])) {
                $values[] = $query[$name];
            }
        }

        return $values;
    }

    /**
     * Group URIs by a query parameter value
     *
     * @return array<string, list<string>> parameter value => URIs
     */
    public function groupBy(string $paramName): array
    {
        $grouped = [];
        foreach ($this->uris as $uri) {
            $query = $this->parseQuery($uri);
            if (isset($query[$paramName])) {
                $key = (string) $query[$paramName];
                $grouped[$key][] = $uri;
            }
        }

        return $grouped;
    }

    /**
     * Map result rows to URIs based on a key column
     *
     * @param list<array<string, mixed>> $rows      Result rows from database
     * @param string                     $keyColumn Column name to use as key for mapping
     */
    public function mapResults(array $rows, string $keyColumn): Results
    {
        $grouped = $this->groupBy($keyColumn);
        $results = [];

        foreach ($this->uris as $uri) {
            $results[$uri] = [];
        }

        foreach ($rows as $row) {
            if (! isset($row[$keyColumn])) {
                continue;
            }

            $keyValue = (string) $row[$keyColumn];
            if (! isset($grouped[$keyValue])) {
                continue;
            }

            foreach ($grouped[$keyValue] as $uri) {
                $results[$uri][] = $row;
            }
        }

        return new Results($results);
    }

    /**
     * Parse query string from URI
     *
     * @return array<string, mixed>
     */
    private function parseQuery(string $uri): array
    {
        $queryString = parse_url($uri, PHP_URL_QUERY);
        if ($queryString === null || $queryString === false) {
            return [];
        }

        parse_str($queryString, $query);

        /** @var array<string, mixed> $query */
        return $query;
    }
}
