<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use stdClass;

use function ctype_digit;
use function end;
use function explode;
use function is_array;
use function sprintf;
use function str_replace;
use function strrpos;
use function substr;
use function trim;

/**
 * JSON pointer helpers shared by the Corvus engine.
 *
 * @internal
 * @psalm-suppress MixedAssignment Data is decoded JSON or request arguments; shapes are checked at the use sites.
 */
final class JsonPointer
{
    /** @return list<string> unescaped segments (`/a~1b/0` => `['a/b', '0']`) */
    public static function segments(string $pointer): array
    {
        if ($pointer === '') {
            return [];
        }

        $segments = [];
        foreach (explode('/', substr($pointer, 1)) as $raw) {
            $segments[] = str_replace(['~1', '~0'], ['/', '~'], $raw);
        }

        return $segments;
    }

    public static function last(string $pointer): string
    {
        $segments = self::segments($pointer);

        return $segments === [] ? '' : end($segments);
    }

    public static function parent(string $pointer): string
    {
        $position = strrpos($pointer, '/');

        return $position === false ? '' : substr($pointer, 0, $position);
    }

    /** `/n/x` => `n.x`, `/arr/2` => `arr[2]` (justinrainbow's property path). */
    public static function property(string $pointer): string
    {
        $path = '';
        foreach (self::segments($pointer) as $segment) {
            $path .= ctype_digit($segment) ? sprintf('[%d]', $segment) : '.' . $segment;
        }

        return trim($path, '.');
    }

    /** Value the pointer addresses in `$data`; null when the path leaves what exists. */
    public static function valueAt(mixed $data, string $pointer): mixed
    {
        foreach (self::segments($pointer) as $segment) {
            $data = self::child($data, $segment);
        }

        return $data;
    }

    private static function child(mixed $data, string $segment): mixed
    {
        if ($data instanceof stdClass) {
            return $data->{$segment} ?? null;
        }

        return is_array($data) ? $data[$segment] ?? null : null;
    }
}
