<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\JsonSchemaException;

interface JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * Handle invalid request object
     *
     * @return void
     */
    public function handleRequestException(ResourceObject $ro, JsonSchemaException $e, string $schemaFile);
}
