<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use function array_map;
use function array_pop;
use function array_values;
use function end;
use function in_array;
use function str_starts_with;

/**
 * Keeps the Corvus result rows that carry the actual keyword failure: drops
 * subschema wrapper rows, rows inside anyOf/oneOf/not/contains/if, and rows
 * that have a failing descendant.
 *
 * @internal
 * @psalm-import-type Row from CorvusErrorMapper
 */
final class ResultLeaves
{
    private const WRAPPER_MESSAGE = 'The value was expected to match the subschema.';

    /** Keywords whose sub-results are alternatives, not errors of their own. */
    private const OPAQUE = ['anyOf', 'oneOf', 'not', 'contains', 'if'];

    /** Parents whose children are names, not keywords. */
    private const NAME_CONTAINERS = ['properties', 'patternProperties', 'definitions', '$defs', 'dependentSchemas', 'dependencies'];

    /**
     * @param list<Row> $rows
     *
     * @return list<Row>
     */
    public function of(array $rows): array
    {
        $kept = $this->failures($rows, $this->opaquePrefixes($rows));
        $locations = array_map(static fn (array $row): string => $row['evaluationLocation'], $kept);
        $leaves = [];
        foreach ($kept as $row) {
            if ($this->hasDescendant($row['evaluationLocation'], $locations)) {
                continue;
            }

            $leaves[] = $row;
        }

        return $leaves;
    }

    /**
     * @param list<Row> $rows
     *
     * @return list<string>
     */
    private function opaquePrefixes(array $rows): array
    {
        $prefixes = [];
        foreach ($rows as $row) {
            if ($row['isMatch'] || ! $this->isOpaque($row['evaluationLocation'])) {
                continue;
            }

            $prefixes[] = $row['evaluationLocation'] . '/';
        }

        return $prefixes;
    }

    /**
     * Failing rows outside opaque keywords, one per evaluation and instance location.
     *
     * @param list<Row>    $rows
     * @param list<string> $opaque
     *
     * @return list<Row>
     */
    private function failures(array $rows, array $opaque): array
    {
        $kept = [];
        foreach ($rows as $row) {
            $location = $row['evaluationLocation'];
            if ($row['isMatch'] || $location === '' || $row['message'] === self::WRAPPER_MESSAGE || $this->isInside($location, $opaque)) {
                continue;
            }

            $kept[$location . '|' . $row['instanceLocation']] = $row;
        }

        return array_values($kept);
    }

    private function isOpaque(string $location): bool
    {
        $segments = JsonPointer::segments($location);
        $keyword = array_pop($segments);

        return in_array($keyword, self::OPAQUE, true) && ! in_array(end($segments), self::NAME_CONTAINERS, true);
    }

    /** @param list<string> $prefixes */
    private function isInside(string $location, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($location, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $locations */
    private function hasDescendant(string $location, array $locations): bool
    {
        $prefix = $location . '/';
        foreach ($locations as $other) {
            if (str_starts_with($other, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
