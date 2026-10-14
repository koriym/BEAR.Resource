<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\JsonSchema\JsonSchemaEngineInterface;
use BEAR\Resource\Module\JsonSchemaModuleTest;

use function extension_loaded;

/** Runs the whole JsonSchemaModule suite against the Corvus engine. */
class CorvusJsonSchemaModuleTest extends JsonSchemaModuleTest
{
    protected function setUp(): void
    {
        if (extension_loaded('corvus_json_schema')) {
            return;
        }

        $this->markTestSkipped('corvus_json_schema extension is not loaded.');
    }

    /** @return class-string<JsonSchemaEngineInterface> */
    protected function engine(): string
    {
        return CorvusEngine::class;
    }
}
