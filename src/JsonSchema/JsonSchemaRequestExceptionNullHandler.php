<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\JsonSchemaException;

class JsonSchemaRequestExceptionNullHandler implements JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handleRequestException(ResourceObject $ro, JsonSchemaException $e, string $schemaFile)
    {
        throw $e;
    }
}
