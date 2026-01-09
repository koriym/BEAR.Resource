<?php

declare(strict_types=1);

namespace BEAR\Resource\Batch;

/**
 * Factory for creating BatchResolver instances
 */
interface BatchResolverFactoryInterface
{
    /**
     * Create a BatchResolver instance from class name
     *
     * @param class-string<BatchResolverInterface> $class
     */
    public function create(string $class): BatchResolverInterface;
}
