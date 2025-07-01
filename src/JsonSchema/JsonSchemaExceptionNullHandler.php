<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\JsonSchemaException;
use Override;

final class JsonSchemaExceptionNullHandler implements JsonSchemaExceptionHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function handle(ResourceObject $ro, JsonSchemaException $e, string $schemaFile): never
    {
        throw $e;
    }
}
