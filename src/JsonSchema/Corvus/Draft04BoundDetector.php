<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use stdClass;

use function is_bool;

/**
 * Finds draft-04's boolean `exclusiveMinimum`/`exclusiveMaximum` in a schema document,
 * looking only at subschema positions (not property names, data or annotations).
 *
 * @internal
 */
final class Draft04BoundDetector
{
    public function usesBooleanBound(mixed $node): bool
    {
        if (! $node instanceof stdClass) {
            return false;
        }

        if (is_bool($node->exclusiveMinimum ?? null) || is_bool($node->exclusiveMaximum ?? null)) {
            return true;
        }

        /** @psalm-suppress MixedAssignment */
        foreach (SchemaKeywords::subschemas($node) as $child) {
            if ($this->usesBooleanBound($child)) {
                return true;
            }
        }

        return false;
    }
}
