<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use BEAR\Resource\Exception\JsonSchemaEngineUnavailableException;
use BEAR\Resource\Interceptor\JsonSchemaInterceptor;
use BEAR\Resource\JsonSchema\Corvus\CorvusEngine;
use BEAR\Resource\Module\JsonSchemaModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use ReflectionProperty;

use function extension_loaded;

class JsonSchemaEngineSelectionTest extends TestCase
{
    public function testJustinrainbowIsTheDefault(): void
    {
        $injector = new Injector(new JsonSchemaModule('/schema', '/validate'));

        $this->assertInstanceOf(JustinrainbowEngine::class, $injector->getInstance(JsonSchemaEngineInterface::class));
        $this->assertSame($this->engineOf($injector), $injector->getInstance(JsonSchemaEngineInterface::class));
    }

    public function testCorvusIsOptIn(): void
    {
        if (! extension_loaded('corvus_json_schema')) {
            $this->markTestSkipped('corvus_json_schema extension is not loaded.');
        }

        $injector = new Injector(new JsonSchemaModule('/schema', '/validate', null, CorvusEngine::class));

        $this->assertInstanceOf(CorvusEngine::class, $this->engineOf($injector));
    }

    public function testCorvusWithoutExtensionFailsLoudly(): void
    {
        if (extension_loaded('corvus_json_schema')) {
            $this->markTestSkipped('corvus_json_schema extension is loaded.');
        }

        $this->expectException(JsonSchemaEngineUnavailableException::class);
        (new Injector(new JsonSchemaModule('/schema', '/validate', null, CorvusEngine::class)))->getInstance(JsonSchemaEngineInterface::class);
    }

    private function engineOf(Injector $injector): JsonSchemaEngineInterface
    {
        $interceptor = $injector->getInstance(JsonSchemaInterceptor::class);
        $engine = (new ReflectionProperty(JsonSchemaInterceptor::class, 'engine'))->getValue($interceptor);
        $this->assertInstanceOf(JsonSchemaEngineInterface::class, $engine);

        return $engine;
    }
}
