<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\HttpRequestCurl;
use BEAR\Resource\HttpRequestHeaders;
use BEAR\Resource\HttpRequestInterface;
use Override;
use Ray\Di\AbstractModule;

/**
 * Provides HttpRequestCurl bindings
 */
final class HttpClientModule extends AbstractModule
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        $this->bind(HttpRequestInterface::class)->to(HttpRequestCurl::class);
        $this->bind(HttpRequestHeaders::class);
    }
}
