<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use BEAR\Resource\Types;

use function in_array;
use function is_array;
use function is_string;

/**
 * @internal
 * @psalm-import-type JsonSchemaValidatorError from Types
 */
final class JsonSchemaErrorMapper
{
    /** Standard row keys consumed by JsonSchemaError; everything else on a 5.x row is treated as a flattened constraint param. */
    private const ROW_KEYS = ['property', 'pointer', 'message', 'constraint', 'context'];

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
            $this->toConstraintViolation($error),
        );
    }

    /**
     * Accepts both upstream shapes:
     *  - 5.x: `constraint` is a string keyword; params are flattened onto the parent row (`minimum`, `maxLength`, etc.)
     *  - 6.x: `constraint` is `array{name: string, params: array<string, mixed>}`
     *
     * @param JsonSchemaValidatorError $error
     *
     * @psalm-suppress MixedAssignment Upstream constraint is mixed; each field is narrowed below.
     */
    private function toConstraintViolation(array $error): ConstraintViolation
    {
        $constraint = $error['constraint'] ?? null;

        if (is_string($constraint)) {
            return new ConstraintViolation($constraint, $this->flattenedParams($error));
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

    /**
     * Collect the 5.x “flattened” params: everything on the row that isn't a standard JsonSchemaError field.
     *
     * @param JsonSchemaValidatorError $error
     *
     * @return array<string, mixed>
     *
     * @psalm-suppress MixedAssignment Upstream row values are mixed; we pass them through as-is.
     */
    private function flattenedParams(array $error): array
    {
        $params = [];
        foreach ($error as $key => $value) {
            if (! is_string($key) || in_array($key, self::ROW_KEYS, true)) {
                continue;
            }

            $params[$key] = $value;
        }

        return $params;
    }
}
