<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\JsonSchema\Interceptor\JsonSchemaInterceptorTest;
use BEAR\Resource\JsonSchema\JsonSchemaEngineInterface;

use function extension_loaded;

/** Runs the whole JsonSchemaInterceptor suite against the Corvus engine. */
class CorvusJsonSchemaInterceptorTest extends JsonSchemaInterceptorTest
{
    protected function setUp(): void
    {
        if (! extension_loaded('corvus_json_schema')) {
            $this->markTestSkipped('corvus_json_schema extension is not loaded.');
        }

        parent::setUp();
    }

    protected function engine(): JsonSchemaEngineInterface
    {
        return new CorvusEngine();
    }
}
