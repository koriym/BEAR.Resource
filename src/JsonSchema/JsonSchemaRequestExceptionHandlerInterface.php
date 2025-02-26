<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\JsonSchemaException;

interface JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * Handle invalid request object
     *
     * @param array<string, mixed> $arguments
     *
     * @return void
     */
    public function handleRequestException(
        array $arguments,
        ResourceObject $ro,
        JsonSchemaException $e,
        string $schemaFile,
    );
}
