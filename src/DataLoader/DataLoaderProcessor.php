<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\LinkType;
use BEAR\Resource\Types;

use function array_values;
use function uri_template;

/**
 * Processes DataLoader-enabled links in batch
 *
 * @psalm-import-type Query from Types
 * @psalm-import-type QueryList from Types
 * @psalm-import-type ObjectList from Types
 */
final class DataLoaderProcessor
{
    /** @var array<class-string<DataLoaderInterface>, DataLoaderInterface> */
    private array $cache = [];

    public function __construct(
        private readonly DataLoaderFactoryInterface $factory,
    ) {
    }

    /**
     * Process DataLoader-enabled links
     *
     * @param ObjectList $annotations
     * @param QueryList  $bodyList
     *
     * @param-out QueryList $bodyList
     *
     * @psalm-suppress ReferenceConstraintViolation
     */
    public function process(array $annotations, LinkType $link, array &$bodyList): void
    {
        // Group URIs by DataLoader class
        /** @var array<class-string<DataLoaderInterface>, array{annotation: Link, uris: array<int, string>}> $loaderGroups */
        $loaderGroups = [];

        foreach ($annotations as $annotation) {
            if (! $annotation instanceof Link || $annotation->crawl !== $link->key) {
                continue;
            }

            if ($annotation->dataLoader === null) {
                continue;
            }

            $loaderClass = $annotation->dataLoader;
            $loaderGroups[$loaderClass] = ['annotation' => $annotation, 'uris' => []];

            foreach ($bodyList as $index => $body) {
                $uri = uri_template($annotation->href, $body);
                $loaderGroups[$loaderClass]['uris'][$index] = $uri;
            }
        }

        // Execute DataLoaders and distribute results
        foreach ($loaderGroups as $loaderClass => $group) {
            $loader = $this->getDataLoader($loaderClass);
            $requests = new Requests(array_values($group['uris']));
            $results = $loader($requests);

            foreach ($group['uris'] as $index => $uri) {
                /** @psalm-suppress MixedAssignment -- Results::get() returns mixed by design */
                $bodyList[$index][$group['annotation']->rel] = $results->get($uri);
            }
        }
    }

    /**
     * Get or create a DataLoader instance
     *
     * @param class-string<DataLoaderInterface> $class
     */
    private function getDataLoader(string $class): DataLoaderInterface
    {
        if (! isset($this->cache[$class])) {
            $this->cache[$class] = $this->factory->create($class);
        }

        return $this->cache[$class];
    }
}
