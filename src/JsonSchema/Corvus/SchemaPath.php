<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use function array_pop;
use function explode;
use function implode;
use function preg_match;
use function rawurldecode;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * Filesystem paths of schema documents.
 *
 * @internal
 */
final class SchemaPath
{
    /** Absolute path of a `file://` URI or absolute path (fragment stripped); null for other schemes. */
    public static function of(string $uri): string|null
    {
        [$uri] = explode('#', $uri, 2);
        if (str_starts_with($uri, 'file://')) {
            return rawurldecode(substr($uri, 7));
        }

        return str_starts_with($uri, '/') ? $uri : null;
    }

    /** Absolute path of a `$ref` (without fragment) found in the document at `$basePath`; null for other schemes. */
    public static function resolve(string $basePath, string $ref): string|null
    {
        $path = self::of($ref);
        if ($path !== null) {
            return $path;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $ref) === 1) {
            return null;
        }

        $parts = explode('/', substr($basePath, 0, (int) strrpos($basePath, '/')) . '/' . $ref);
        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } elseif ($part !== '.') {
                $resolved[] = $part;
            }
        }

        return implode('/', $resolved);
    }
}
