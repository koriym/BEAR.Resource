<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\OptionsMethods;
use BEAR\Resource\OptionsRenderer;
use BEAR\Resource\RenderInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

class OptionsMethodModuleTest extends TestCase
{
    public function testOptionsMethodModule(): void
    {
        $injector = new Injector(new OptionsMethodModule(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(OptionsMethods::class);
            }
        }));
        $renderer = $injector->getInstance(RenderInterface::class, 'options');
        $this->assertInstanceOf(OptionsRenderer::class, $renderer);
    }
}
