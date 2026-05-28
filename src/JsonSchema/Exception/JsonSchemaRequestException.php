<?php

declare(strict_types=1);

namespace BEAR\Resource\Exception;

/**
 * Thrown when a JSON Schema validation against a method's input parameters fails.
 *
 * Semantically a 4xx (client-supplied bad input). `JsonSchemaRequestExceptionHandlerInterface`
 * receives this concrete subtype. Catching `JsonSchemaException` still matches.
 */
final class JsonSchemaRequestException extends JsonSchemaException
{
}
