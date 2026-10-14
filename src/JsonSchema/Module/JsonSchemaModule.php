<?php

declare(strict_types=1);

namespace BEAR\Resource\Module;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Interceptor\JsonSchemaInterceptor;
use BEAR\Resource\Interceptor\JsonSchemaInterceptorInterface;
use BEAR\Resource\JsonSchema\JsonSchemaEngineInterface;
use BEAR\Resource\JsonSchema\JustinrainbowEngine;
use BEAR\Resource\JsonSchemaExceptionHandlerInterface;
use BEAR\Resource\JsonSchemaExceptionNullHandler;
use BEAR\Resource\JsonSchemaRequestExceptionHandlerInterface;
use BEAR\Resource\JsonSchemaRequestExceptionNullHandler;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class JsonSchemaModule extends AbstractModule
{
    /**
     * @param string                                  $jsonSchemaDir   Json-schema json file directory
     * @param string                                  $jsonValidateDir Json-schema validator json file directory
     * @param class-string<JsonSchemaEngineInterface> $engine          Validation engine; pass CorvusEngine::class to opt in to the corvus_json_schema extension (schemas without `$schema` compile as draft-07)
     */
    public function __construct(
        private readonly string $jsonSchemaDir = '',
        private readonly string $jsonValidateDir = '',
        AbstractModule|null $module = null,
        private readonly string $engine = JustinrainbowEngine::class,
    ) {
        parent::__construct($module);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function configure(): void
    {
        $this->bind()->annotatedWith('json_schema_dir')->toInstance($this->jsonSchemaDir);
        $this->bind()->annotatedWith('json_validate_dir')->toInstance($this->jsonValidateDir);
        $this->bind(JsonSchemaExceptionHandlerInterface::class)->to(JsonSchemaExceptionNullHandler::class);
        $this->bind(JsonSchemaRequestExceptionHandlerInterface::class)->to(JsonSchemaRequestExceptionNullHandler::class);
        $this->bind(JsonSchemaEngineInterface::class)->to($this->engine)->in(Scope::SINGLETON);
        $this->bind(JsonSchemaInterceptorInterface::class)->to(JsonSchemaInterceptor::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(JsonSchema::class),
            [JsonSchemaInterceptorInterface::class],
        );
    }
}
