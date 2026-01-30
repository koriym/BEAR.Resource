<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\Resource;
use BEAR\Resource\ResourceClient;
use BEAR\Resource\ResourceClientInterface;
use BEAR\Resource\ResourceInterface;
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
        $this->assertInstanceOf(Resource::class, $resource);
    }

    public function testConfigureResourceClient(): void
    {
        $resource = (new Injector(new ResourceModule('FakeVendor/Sandbox')))->getInstance(ResourceClientInterface::class);
        $this->assertInstanceOf(ResourceClient::class, $resource);
    }
}
