<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\Exception\UnsupportedSchemaException;
use stdClass;

use function clearstatcache;
use function explode;
use function file_get_contents;
use function filemtime;
use function filesize;
use function is_bool;
use function is_file;
use function is_string;
use function json_decode;
use function preg_match;
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
     * Path of the document a result row's schema location is relative to, found
     * by walking its evaluation location (e.g. `/properties/name/$ref/required`) from the root document.
     */
    public function documentOf(string $rootPath, string $evaluationLocation): string
    {
        $path = $rootPath;
        $node = $this->load($rootPath);
        foreach (JsonPointer::segments($evaluationLocation) as $segment) {
            [$path, $node] = $this->settle($path, $node, $segment);
            if ($segment === '$ref' && $node instanceof stdClass && is_string($node->{'$ref'} ?? null)) {
                [$path, $node] = $this->follow($path, $node->{'$ref'});

                continue;
            }

            $node = JsonPointer::valueAt($node, '/' . JsonPointer::escape($segment));
        }

        return $path;
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
     * Corvus leaves a `$ref` hop out of the location when the `$ref` is the only thing
     * evaluated: the schema has nothing else, or the dialect (draft-07 and earlier)
     * ignores the siblings. Follow such hops until the node owns the next segment.
     *
     * @return array{string, mixed}
     */
    private function settle(string $path, mixed $node, string $next): array
    {
        $visited = [];
        while ($node instanceof stdClass && $next !== '$ref' && (! isset($node->{$next}) || $this->ignoresRefSiblings($path))) {
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

    /** Draft-07 and earlier, which is also what Corvus compiles a document without `$schema` as. */
    private function ignoresRefSiblings(string $path): bool
    {
        $document = $this->load($path);
        $declared = $document instanceof stdClass ? $document->{'$schema'} ?? null : null;

        return ! is_string($declared) || preg_match('#^http://json-schema\.org/draft-0[467]/schema#', $declared) === 1;
    }

    /**
     * Target of a `$ref`: a JSON pointer or a named anchor in a file, or a resource
     * embedded with `$id` in the current document.
     *
     * @return array{string, mixed} document path and node
     */
    private function follow(string $basePath, string $ref): array
    {
        [$uri, $fragment] = explode('#', $ref, 2) + [1 => ''];
        $fragment = rawurldecode($fragment);
        $path = $uri === '' ? $basePath : SchemaPath::resolve($basePath, $uri);
        if ($path !== null && $this->stamp($path) !== null) {
            return [$path, $this->fragment($this->load($path), $fragment)];
        }

        $resource = $this->find($this->load($basePath), static fn (stdClass $node): bool => is_string($node->{'$id'} ?? null)
            && ($node->{'$id'} === $uri || SchemaPath::resolve($basePath, $node->{'$id'}) === $path));

        return [$basePath, $this->fragment($resource, $fragment)];
    }

    private function fragment(mixed $resource, string $fragment): mixed
    {
        if ($fragment === '' || str_starts_with($fragment, '/')) {
            return JsonPointer::valueAt($resource, $fragment);
        }

        return $this->find($resource, static fn (stdClass $node): bool => ($node->{'$anchor'} ?? null) === $fragment || ($node->{'$id'} ?? null) === '#' . $fragment);
    }

    /** @param callable(stdClass): bool $matches */
    private function find(mixed $node, callable $matches): stdClass|null
    {
        if (! $node instanceof stdClass) {
            return null;
        }

        if ($matches($node)) {
            return $node;
        }

        foreach (SchemaKeywords::subschemas($node) as $child) {
            $found = $this->find($child, $matches);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
