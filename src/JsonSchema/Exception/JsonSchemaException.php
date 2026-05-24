<?php

declare(strict_types=1);

namespace BEAR\Resource\Exception;

use BEAR\Resource\JsonSchema\JsonSchemaError;
use LogicException;

final class JsonSchemaException extends LogicException implements ExceptionInterface
{
    /** @param list<JsonSchemaError> $errors */
    public function __construct(
        string $message,
        int $code = 0,
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $code);
    }

    /** @return list<JsonSchemaError> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
