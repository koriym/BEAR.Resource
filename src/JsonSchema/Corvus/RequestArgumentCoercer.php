<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use stdClass;

use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;

/**
 * Turns string request arguments into the scalar type the schema declares for them.
 *
 * Scope is deliberately small: top-level properties only, string input only, and
 * only when the declared type does not accept a string. Arguments the resource
 * method type-hints (`int $id`) are already typed when the interceptor runs; this
 * covers untyped / `string` / `mixed` parameters that a schema types as
 * integer, number or boolean. Nested values are left untouched. `$ref` chains on
 * the root and on a property are followed, each against the file that holds it.
 *
 * Casting is strict: boolean accepts only `true` / `false`, integer only `^-?\d+$`.
 *
 * @internal
 * @psalm-suppress MixedAssignment, MixedArgument, PossiblyNullArgument Schema nodes are decoded JSON; shapes are checked at the use sites.
 */
final class RequestArgumentCoercer
{
    public function __construct(private SchemaDocuments $documents)
    {
    }

    /**
     * @param array<array-key, mixed> $arguments
     *
     * @return array<array-key, mixed>
     */
    public function coerce(array $arguments, string $schemaPath): array
    {
        [$base, $schema] = $this->documents->deref($schemaPath, $this->documents->load($schemaPath));
        $properties = $schema instanceof stdClass ? ($schema->properties ?? null) : null;
        if (! $properties instanceof stdClass) {
            return $arguments;
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($arguments as $name => $value) {
            if (! is_string($value) || ! isset($properties->{$name})) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            [, $node] = $this->documents->deref($base, $properties->{$name});
            if (! ($node instanceof stdClass)) {
                continue;
            }

            $arguments[$name] = $this->cast($value, $node->type ?? null);
        }

        return $arguments;
    }

    private function cast(string $value, mixed $type): mixed
    {
        $types = is_array($type) ? $type : [$type];
        if (in_array('string', $types, true)) {
            return $value;
        }

        return $this->integer($value, $types) ?? $this->number($value, $types) ?? $this->boolean($value, $types) ?? $value;
    }

    /** @param array<mixed> $types */
    private function integer(string $value, array $types): int|null
    {
        return in_array('integer', $types, true) && preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : null;
    }

    /** @param array<mixed> $types */
    private function number(string $value, array $types): int|float|null
    {
        if (! in_array('number', $types, true) || ! is_numeric($value)) {
            return null;
        }

        return preg_match('/[.eE]/', $value) === 1 ? (float) $value : (int) $value;
    }

    /** @param array<mixed> $types */
    private function boolean(string $value, array $types): bool|null
    {
        return in_array('boolean', $types, true) && in_array($value, ['true', 'false'], true) ? $value === 'true' : null;
    }
}
