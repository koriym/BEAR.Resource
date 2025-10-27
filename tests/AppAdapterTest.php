<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\ResourceNotFoundException;
use FakeVendor\Sandbox\Module\AppModule;
use FakeVendor\Sandbox\Resource\Page\Index;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Compiler;
use Ray\Di\Injector;

class AppAdapterTest extends TestCase
{
    private AppAdapter $appAdapter;

    protected function setUp(): void
    {
        $injector = new Injector(new AppModule(), __DIR__ . '/tmp');
        $this->appAdapter = new AppAdapter($injector, 'FakeVendor\Sandbox');
    }

    public function testGet(): void
    {
        $index = $this->appAdapter->get(new Uri('page://self/index'));
        $this->assertInstanceOf(Index::class, $index);
    }

    public function testNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->appAdapter->get(new Uri('page://self/__not_found__'));
    }

    public function testGetWithCompiler(): void
    {
        $injector = $this->getCompiledInjector();
        $appAdapter = new AppAdapter($injector, 'FakeVendor\Sandbox');
        $index = $appAdapter->get(new Uri('page://self/index'));
        $this->assertInstanceOf(Index::class, $index);
    }

    public function testNotFoundWithCompiler(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile(new AppModule(), $scriptDir);
        $injector = new CompiledInjector($scriptDir);
        $appAdapter = new AppAdapter($injector, 'FakeVendor\Sandbox');
        $appAdapter->get(new Uri('page://self/__not_found__'));
    }

    private function getCompiledInjector(): CompiledInjector
    {
        $scriptDir = __DIR__ . '/tmp';
        (new Compiler())->compile(new AppModule(), $scriptDir);

        return new CompiledInjector($scriptDir);
    }
}
