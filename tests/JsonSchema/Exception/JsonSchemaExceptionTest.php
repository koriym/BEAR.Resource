<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Exception;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use LogicException;
use PHPUnit\Framework\TestCase;

class JsonSchemaExceptionTest extends TestCase
{
    public function testDefaultsToEmptyErrors(): void
    {
        $e = new JsonSchemaException('message', 500);

        $this->assertSame('message', $e->getMessage());
        $this->assertSame(500, $e->getCode());
        $this->assertSame([], $e->getErrors());
    }

    public function testCarriesStructuredErrors(): void
    {
        $error = new JsonSchemaError(
            'age',
            '/age',
            'Must have a minimum value of 20',
            new ConstraintViolation('minimum', ['minimum' => 20]),
        );
        $e = new JsonSchemaException('message', 500, [$error]);

        $this->assertCount(1, $e->getErrors());
        $this->assertSame($error, $e->getErrors()[0]);
    }

    public function testIsLogicException(): void
    {
        $this->assertInstanceOf(LogicException::class, new JsonSchemaException('m', 0));
    }
}
