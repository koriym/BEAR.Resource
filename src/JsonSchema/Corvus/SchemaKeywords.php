<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use stdClass;

use function array_values;
use function ctype_digit;
use function get_object_vars;
use function in_array;
use function is_array;

/**
 * Where subschemas sit in a schema: the keyword positions shared by every draft.
 *
 * Unknown keywords (annotations such as `x-example`) hold data, not schemas.
 *
 * @internal
 * @psalm-suppress MixedAssignment Schema nodes are decoded JSON.
 */
final class SchemaKeywords
{
    /** Children are names, each holding a subschema. */
    private const NAMED = ['properties', 'patternProperties', 'definitions', '$defs', 'dependentSchemas', 'dependencies'];

    /** Holds a list of subschemas. */
    private const LIST = ['allOf', 'anyOf', 'oneOf', 'prefixItems'];

    /** Holds one subschema. `items` is handled apart: one subschema, or a list before 2020-12. */
    private const SINGLE = ['additionalItems', 'additionalProperties', 'unevaluatedItems', 'unevaluatedProperties', 'not', 'if', 'then', 'else', 'contains', 'propertyNames'];

    /** Lead to the schema they reference. */
    private const REFERENCES = ['$ref', '$dynamicRef', '$recursiveRef'];

    /**
     * The keyword the last segment of a schema location names, or null when the
     * location ends at a subschema reached by name, index or reference (or at the root).
     *
     * @param list<string> $segments
     */
    public static function keywordAt(array $segments): string|null
    {
        $keyword = null;
        $expects = 'keyword';
        foreach ($segments as $segment) {
            if ($expects === 'member' || ($expects === 'items' && ctype_digit($segment))) {
                [$keyword, $expects] = [null, 'keyword'];
                continue;
            }

            [$keyword, $expects] = self::step($segment);
        }

        return $keyword;
    }

    /** @return list<mixed> */
    public static function subschemas(stdClass $schema): array
    {
        $children = [];
        foreach (get_object_vars($schema) as $keyword => $value) {
            if (in_array($keyword, self::NAMED, true) && $value instanceof stdClass) {
                $children = [...$children, ...array_values(get_object_vars($value))];
                continue;
            }

            if ($keyword !== 'items' && ! in_array($keyword, self::LIST, true) && ! in_array($keyword, self::SINGLE, true)) {
                continue;
            }

            $children = [...$children, ...(is_array($value) ? array_values($value) : [$value])];
        }

        return $children;
    }

    /** @return array{string|null, string} [keyword the segment names, what the next segment is] */
    private static function step(string $segment): array
    {
        return match (true) {
            in_array($segment, self::REFERENCES, true) => [null, 'keyword'],
            in_array($segment, self::NAMED, true), in_array($segment, self::LIST, true) => [$segment, 'member'],
            $segment === 'items' => [$segment, 'items'],
            in_array($segment, self::SINGLE, true) => [$segment, 'keyword'],
            default => [$segment, 'data'],
        };
    }
}
