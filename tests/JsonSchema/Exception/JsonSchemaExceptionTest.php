<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Exception;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\JsonSchema\JsonSchemaErrors;
use LogicException;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class JsonSchemaExceptionTest extends TestCase
{
    public function testDefaultsToEmptyErrors(): void
    {
        $e = new JsonSchemaException('message', 500);

        $this->assertSame('message', $e->getMessage());
        $this->assertSame(500, $e->getCode());
        $this->assertInstanceOf(JsonSchemaErrors::class, $e->getErrors());
        $this->assertFalse($e->getErrors()->hasErrors());
        $this->assertCount(0, $e->getErrors());
    }

    public function testCarriesStructuredErrors(): void
    {
        $error = new JsonSchemaError(
            'age',
            '/age',
            'Must have a minimum value of 20',
            new ConstraintViolation('minimum', ['minimum' => 20]),
        );
        $errors = new JsonSchemaErrors([$error]);
        $e = new JsonSchemaException('message', 500, $errors);

        $this->assertSame($errors, $e->getErrors());
        $this->assertCount(1, $e->getErrors());
        $this->assertSame([$error], iterator_to_array($e->getErrors(), false));
    }

    public function testIsLogicException(): void
    {
        $this->assertInstanceOf(LogicException::class, new JsonSchemaException('m', 0));
    }
}
