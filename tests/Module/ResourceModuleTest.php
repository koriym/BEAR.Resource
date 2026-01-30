<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourcePure;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class ResourceModuleTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testConfigure(): void
    {
        $resource = (new Injector(new ResourceModule('FakeVendor/Sandbox')))->getInstance(ResourceInterface::class);
        $this->assertInstanceOf(ResourcePure::class, $resource);
    }
}
