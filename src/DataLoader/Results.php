<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use function array_key_exists;

/**
 * DataLoader results mapped by URI
 */
final class Results
{
    /** @param array<string, mixed> $results URI => result mapping */
    public function __construct(
        private readonly array $results,
    ) {
    }

    /**
     * Get result for a specific URI
     */
    public function get(string $uri): mixed
    {
        return $this->results[$uri] ?? null;
    }

    /**
     * Check if result exists for a URI
     */
    public function has(string $uri): bool
    {
        return array_key_exists($uri, $this->results);
    }

    /**
     * Get all results as array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->results;
    }
}
