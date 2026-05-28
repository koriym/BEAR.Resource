<?php

declare(strict_types=1);

namespace BEAR\Resource\Exception;

/**
 * Thrown when a JSON Schema validation against the resource's response body fails.
 *
 * Semantically a 5xx (the resource produced output that doesn't match its declared schema).
 * `JsonSchemaExceptionHandlerInterface` receives this concrete subtype. Catching
 * `JsonSchemaException` still matches.
 */
final class JsonSchemaResponseException extends JsonSchemaException
{
}
