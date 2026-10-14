<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use BEAR\Resource\Types;
use stdClass;

/**
 * Validation engine behind the JSON Schema interceptor.
 *
 * Engines return justinrainbow-shaped error rows (`property`, `pointer`,
 * `message`, `constraint: {name, params}`), which JsonSchemaErrorMapper turns
 * into JsonSchemaError and renders `errorMessage` templates for.
 *
 * @psalm-import-type Query from Types
 * @psalm-import-type JsonSchemaValidatorErrors from Types
 */
interface JsonSchemaEngineInterface
{
    /**
     * @param Query $arguments Named arguments of the resource method
     *
     * @return JsonSchemaValidatorErrors Empty when valid
     */
    public function validateRequest(array $arguments, string $schemaFile): array;

    /**
     * @param array<mixed>|stdClass $body Decoded JSON response (objects as stdClass)
     *
     * @return JsonSchemaValidatorErrors Empty when valid
     */
    public function validateResponse(array|stdClass $body, string $schemaFile): array;
}
