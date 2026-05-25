<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use BEAR\Resource\Types;

use function is_array;
use function is_string;

/**
 * @internal
 * @psalm-import-type JsonSchemaValidatorError from Types
 */
final class JsonSchemaErrorMapper
{
    /**
     * Normalize one justinrainbow validator error row into a typed JsonSchemaError.
     *
     * @param JsonSchemaValidatorError $error
     *
     * @psalm-suppress MixedAssignment Upstream validator returns mixed-typed row values; each field is narrowed below.
     */
    public function toJsonSchemaError(array $error): JsonSchemaError
    {
        $propertyRaw = $error['property'] ?? null;
        $pointerRaw = $error['pointer'] ?? null;
        $messageRaw = $error['message'] ?? null;

        return new JsonSchemaError(
            is_string($propertyRaw) ? $propertyRaw : '',
            is_string($pointerRaw) ? $pointerRaw : '',
            is_string($messageRaw) ? $messageRaw : '',
            $this->toConstraintViolation($error['constraint'] ?? null),
        );
    }

    /**
     * Accepts both upstream shapes:
     *  - 5.x: a string keyword (params flattened into the parent row)
     *  - 6.x: `array{name: string, params: array<string, mixed>}`
     *
     * @psalm-suppress MixedAssignment Upstream constraint is mixed; each field is narrowed below.
     */
    private function toConstraintViolation(mixed $constraint): ConstraintViolation
    {
        if (is_string($constraint)) {
            return new ConstraintViolation($constraint, []);
        }

        if (! is_array($constraint)) {
            return new ConstraintViolation('unknown', []);
        }

        $nameRaw = $constraint['name'] ?? null;
        $paramsRaw = $constraint['params'] ?? null;
        /** @var array<string, mixed> $params */
        $params = is_array($paramsRaw) ? $paramsRaw : [];

        return new ConstraintViolation(
            is_string($nameRaw) ? $nameRaw : 'unknown',
            $params,
        );
    }
}
