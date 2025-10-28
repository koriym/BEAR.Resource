<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\JsonSchemaException;

/** @psalm-import-type Query from Types */
interface JsonSchemaRequestExceptionHandlerInterface
{
    /**
     * Handle invalid request object
     *
     * @param Query $arguments
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
