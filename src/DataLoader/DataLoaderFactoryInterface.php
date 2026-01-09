<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

/**
 * Factory for creating DataLoader instances
 */
interface DataLoaderFactoryInterface
{
    /**
     * Create a DataLoader instance from class name
     *
     * @param class-string<DataLoaderInterface> $class
     */
    public function create(string $class): DataLoaderInterface;
}
