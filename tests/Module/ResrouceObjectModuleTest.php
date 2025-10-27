<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\ResourceObject;
use FakeVendor\Sandbox\Resource\Page\HelloWorld;
use FakeVendor\Sandbox\Resource\Page\Index;
use Generator;
use PHPUnit\Framework\TestCase;
use Ray\Compiler\CompiledInjector;
use Ray\Compiler\Compiler;
use Ray\Di\AbstractModule;

use function array_map;
use function glob;
use function iterator_to_array;
use function unlink;

use const GLOB_BRACE;

final class ResrouceObjectModuleTest extends TestCase
{
    protected function setUp(): void
    {
        @unlink(__DIR__ . '/tmp/compiled');
        array_map('unlink', (array) glob(__DIR__ . '/tmp/{*.php}', GLOB_BRACE)); // @phpstan-ignore-line
    }

    public function testConfigureWithGenerator(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        $module = new class ($this->getResourceObjectGenerator()) extends AbstractModule {
            public function __construct(private Generator $generator)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->install(new ResourceObjectModule($this->generator));
            }
        };
        (new Compiler())->compile($module, $scriptDir);
        $injector = new CompiledInjector($scriptDir);

        $this->assertInstanceOf(Index::class, $injector->getInstance(Index::class));
        $this->assertInstanceOf(HelloWorld::class, $injector->getInstance(HelloWorld::class));
    }

    public function testConfigureWithArray(): void
    {
        $scriptDir = __DIR__ . '/tmp';
        $module = new class (iterator_to_array($this->getResourceObjectGenerator())) extends AbstractModule {
            /** @param array<class-string<ResourceObject>> $resourceObjects */
            public function __construct(private array $resourceObjects)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->install(new ResourceObjectModule($this->resourceObjects));
            }
        };
        (new Compiler())->compile($module, $scriptDir);
        $injector = new CompiledInjector($scriptDir);

        $this->assertInstanceOf(Index::class, $injector->getInstance(Index::class));
        $this->assertInstanceOf(HelloWorld::class, $injector->getInstance(HelloWorld::class));
    }

    /** @return Generator<class-string<ResourceObject>> */
    private function getResourceObjectGenerator(): Generator
    {
        yield Index::class;
        yield HelloWorld::class;
    }
}
