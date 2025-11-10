<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Annotation\AppName;
use Ray\Di\AbstractModule;

class FakeSchemeModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(SchemeCollectionInterface::class)->toProvider(FakeSchemeCollectionProvider::class);
        $this->bind()->annotatedWith(AppName::class)->toInstance('TestApp');
    }
}
