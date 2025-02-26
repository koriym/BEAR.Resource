<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\JsonSchemaException;

class JsonSchemaRequestExceptionNullHandler implements JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handleRequestException(
        array $arguments,
        ResourceObject $ro,
        JsonSchemaException $e,
        string $schemaFile,
    ) {
        throw $e;
    }
}
