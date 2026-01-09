<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\DataLoader\DataLoaderFactory;
use BEAR\Resource\DataLoader\DataLoaderFactoryInterface;
use BEAR\Resource\DataLoader\DataLoaderProcessor;
use Override;
use Ray\Di\AbstractModule;

/**
 * Provides DataLoader support for Crawl N+1 problem resolution
 *
 * Install this module to enable DataLoader functionality that batches
 * multiple resource requests into a single operation during link crawling.
 */
final class DataLoaderModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(DataLoaderFactoryInterface::class)->to(DataLoaderFactory::class);
        $this->bind(DataLoaderProcessor::class);
    }
}
