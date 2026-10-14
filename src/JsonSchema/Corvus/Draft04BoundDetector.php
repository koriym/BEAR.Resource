<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use stdClass;

use function in_array;
use function is_array;
use function is_bool;

/**
 * Finds draft-04's boolean `exclusiveMinimum`/`exclusiveMaximum` in a schema document.
 *
 * Children of `properties`, `patternProperties`, `definitions`, `$defs`,
 * `dependentSchemas` and `dependencies` are names, so a property called
 * `exclusiveMinimum` is not a bound; `enum`, `const`, `default` and `examples`
 * hold data, not schemas.
 *
 * @internal
 * @psalm-suppress MixedAssignment Schema nodes are decoded JSON; shapes are checked at the use sites.
 */
final class Draft04BoundDetector
{
    private const NAME_CONTAINERS = ['properties', 'patternProperties', 'definitions', '$defs', 'dependentSchemas', 'dependencies'];
    private const DATA = ['enum', 'const', 'default', 'examples'];

    public function usesBooleanBound(mixed $node): bool
    {
        return $this->inSchema($node);
    }

    private function inSchema(mixed $node): bool
    {
        if (is_array($node)) {
            return $this->inList($node);
        }

        if (! $node instanceof stdClass) {
            return false;
        }

        if (is_bool($node->exclusiveMinimum ?? null) || is_bool($node->exclusiveMaximum ?? null)) {
            return true;
        }

        foreach ((array) $node as $keyword => $child) {
            if (in_array($keyword, self::DATA, true)) {
                continue;
            }

            $found = in_array($keyword, self::NAME_CONTAINERS, true) ? $this->inNamed($child) : $this->inSchema($child);
            if ($found) {
                return true;
            }
        }

        return false;
    }

    /** @param array<mixed> $list */
    private function inList(array $list): bool
    {
        foreach ($list as $child) {
            if ($this->inSchema($child)) {
                return true;
            }
        }

        return false;
    }

    private function inNamed(mixed $container): bool
    {
        if ($container instanceof stdClass) {
            return $this->inList((array) $container);
        }

        return is_array($container) && $this->inList($container);
    }
}
