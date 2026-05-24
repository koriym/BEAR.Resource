<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

/** @psalm-immutable */
final readonly class JsonSchemaError
{
    public function __construct(
        public string $property,
        public string $pointer,
        public string $message,
        public ConstraintViolation $constraint,
    ) {
    }
}
