<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\Exception\UnsupportedSchemaException;
use stdClass;

use function array_pop;
use function clearstatcache;
use function explode;
use function file_get_contents;
use function filemtime;
use function filesize;
use function is_array;
use function is_bool;
use function is_file;
use function is_string;
use function json_decode;
use function preg_replace;
use function rawurldecode;
use function sprintf;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * Loads schema files (cached per path, mtime and size) and follows `$ref` through them.
 *
 * Used to feed the Corvus resolver and to recover constraint params from the
 * schema node a result row points at.
 *
 * @internal
 * @psalm-suppress MixedAssignment, MixedArgument, PossiblyNullArgument Schema nodes are decoded JSON; shapes are checked at the use sites.
 */
final class SchemaDocuments
{
    /** @var array<string, array{string, mixed}> */
    private array $documents = [];
    private Draft04BoundDetector $draft04;

    public function __construct()
    {
        $this->draft04 = new Draft04BoundDetector();
    }

    /** Corvus resolver callback: absolute `file://` URI to decoded document, or null. */
    public function resolve(string $uri): stdClass|bool|null
    {
        $path = SchemaPath::of($uri);
        if ($path === null) {
            return null;
        }

        $document = $this->load($path);

        return $document instanceof stdClass || is_bool($document) ? $document : null;
    }

    public function load(string $path): mixed
    {
        $stamp = $this->stamp($path);
        if ($stamp === null) {
            return null;
        }

        $cached = $this->documents[$path] ?? null;
        if ($cached !== null && $cached[0] === $stamp) {
            return $cached[1];
        }

        /** @psalm-suppress MixedAssignment */
        $document = json_decode((string) file_get_contents($path), false, 512, JSON_THROW_ON_ERROR);
        if ($document instanceof stdClass) {
            $this->normalizeDialect($document, $path);
        }

        $this->documents[$path] = [$stamp, $document];

        return $document;
    }

    /** Change marker of a file (mtime and size); null when it is not a file. */
    public function stamp(string $path): string|null
    {
        clearstatcache(true, $path);

        return is_file($path) ? sprintf('%d:%d', (int) filemtime($path), (int) filesize($path)) : null;
    }

    /**
     * Walk an evaluation location (e.g. `/properties/name/$ref/required`) from the root
     * document and return the schema object that owns the last keyword.
     *
     * @return array{stdClass, string}|null [schema object, keyword]; null when the walk leaves what can be resolved
     */
    public function locate(string $rootPath, string $evaluationLocation): array|null
    {
        $segments = JsonPointer::segments($evaluationLocation);
        $keyword = array_pop($segments);
        if ($keyword === null) {
            return null;
        }

        [$path, $node] = $this->settle($rootPath, $this->load($rootPath), $segments[0] ?? $keyword);
        foreach ($segments as $i => $segment) {
            if ($segment === '$ref' && $node instanceof stdClass && is_string($node->{'$ref'} ?? null)) {
                [$path, $node] = $this->follow($path, $node->{'$ref'});
                [$path, $node] = $this->settle($path, $node, $segments[$i + 1] ?? $keyword);

                continue;
            }

            $node = $this->child($node, $segment);
        }

        return $node instanceof stdClass ? [$node, $keyword] : null;
    }

    /**
     * Follow a chain of `$ref`s on a schema object.
     *
     * @return array{string, mixed} target document path and node; the input when there is no `$ref`
     */
    public function deref(string $path, mixed $node): array
    {
        $visited = [];
        while ($node instanceof stdClass) {
            $ref = $node->{'$ref'} ?? null;
            if (! is_string($ref) || isset($visited[$path . '#' . $ref])) {
                break;
            }

            $visited[$path . '#' . $ref] = true;
            [$path, $node] = $this->follow($path, $ref);
        }

        return [$path, $node];
    }

    /**
     * Corvus leaves a `$ref` hop out of the location when the schema has nothing else to
     * evaluate; follow such hops until the node can own the next segment.
     *
     * @return array{string, mixed}
     */
    private function settle(string $path, mixed $node, string $next): array
    {
        $visited = [];
        while ($node instanceof stdClass && $next !== '$ref' && ! isset($node->{$next})) {
            $ref = $node->{'$ref'} ?? null;
            if (! is_string($ref) || isset($visited[$path . '#' . $ref])) {
                break;
            }

            $visited[$path . '#' . $ref] = true;
            [$path, $node] = $this->follow($path, $ref);
        }

        return [$path, $node];
    }

    /**
     * Corvus reads `$schema` at the document root only and does not recognise the
     * `https://` spelling of the draft-04/06/07 ids (it would fall back to 2020-12),
     * so rewrite it. Documents without `$schema` are compiled as draft-07, which does
     * not know draft-04's boolean `exclusiveMinimum`/`exclusiveMaximum`; fail instead
     * of silently ignoring them.
     */
    private function normalizeDialect(stdClass $document, string $path): void
    {
        $declared = $document->{'$schema'} ?? null;
        if (is_string($declared)) {
            $document->{'$schema'} = preg_replace('#^https://(json-schema\.org/draft-0[467]/schema)#', 'http://$1', $declared);

            return;
        }

        if ($this->draft04->usesBooleanBound($document)) {
            throw new UnsupportedSchemaException(sprintf('%s uses draft-04 boolean exclusiveMinimum/exclusiveMaximum without "$schema". Declare "$schema": "http://json-schema.org/draft-04/schema#".', $path));
        }
    }

    /** @return array{string, mixed} */
    private function follow(string $basePath, string $ref): array
    {
        [$ref, $fragment] = explode('#', $ref, 2) + [1 => ''];

        $path = $ref === '' ? $basePath : SchemaPath::resolve($basePath, $ref);
        if ($path === null) {
            return [$basePath, null];
        }

        /** @psalm-suppress MixedAssignment */
        $node = $this->load($path);
        $fragment = rawurldecode($fragment);
        if ($fragment !== '' && ! str_starts_with($fragment, '/')) {
            return [$path, null]; // named anchors are not followed
        }

        foreach (JsonPointer::segments($fragment) as $segment) {
            $node = $this->child($node, $segment);
        }

        return [$path, $node];
    }

    private function child(mixed $node, string $segment): mixed
    {
        if ($node instanceof stdClass) {
            return $node->{$segment} ?? null;
        }

        return is_array($node) ? $node[(int) $segment] ?? null : null;
    }
}
