<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

/**
 * @psalm-type ConstraintName = 'type'|'required'|'pattern'|'minLength'|'maxLength'
 *     |'minimum'|'maximum'|'multipleOf'|'enum'|'const'|'format'
 *     |'minItems'|'maxItems'|'uniqueItems'|'minProperties'|'maxProperties'
 *
 * @psalm-immutable
 */
final readonly class ConstraintViolation
{
    /**
     * @param ConstraintName|string $name   Constraint keyword (typed as literal-union for known JSON Schema keywords; runtime stays string for forward compatibility)
     * @param array<string, mixed>  $params
     */
    public function __construct(
        public string $name,
        public array $params,
    ) {
    }
}
