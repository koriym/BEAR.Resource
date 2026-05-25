<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use Countable;
use Generator;
use IteratorAggregate;
use Override;

use function count;

/**
 * Collection of structured JSON Schema validation errors.
 *
 * Immutable container exposed by JsonSchemaException::getErrors(). Provides
 * iteration, count, and property-keyed grouping — the last being the core
 * use case for handlers that re-render a form with field-keyed errors.
 *
 * @psalm-immutable
 * @implements IteratorAggregate<int, JsonSchemaError>
 */
final readonly class JsonSchemaErrors implements Countable, IteratorAggregate
{
    /** @param list<JsonSchemaError> $errors */
    public function __construct(
        public array $errors,
    ) {
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    #[Override]
    public function count(): int
    {
        return count($this->errors);
    }

    /** @return Generator<int, JsonSchemaError> */
    #[Override]
    public function getIterator(): Generator
    {
        yield from $this->errors;
    }

    /**
     * Group errors by their property path.
     *
     * Most useful for handlers that build a `{field: [messages]}` body for a
     * 422 response or re-render a form with per-field error annotations.
     *
     * @return array<string, list<JsonSchemaError>>
     */
    public function byProperty(): array
    {
        $grouped = [];
        foreach ($this->errors as $error) {
            $grouped[$error->property][] = $error;
        }

        return $grouped;
    }
}
