<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\Exception\JsonSchemaResponseException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\JsonSchema\JsonSchemaErrors;
use PHPUnit\Framework\TestCase;

class JsonSchemaResponseExceptionTest extends TestCase
{
    public function testExtendsJsonSchemaException(): void
    {
        $e = new JsonSchemaResponseException('bad output', Code::ERROR);

        $this->assertInstanceOf(JsonSchemaException::class, $e);
        $this->assertSame('bad output', $e->getMessage());
        $this->assertSame(Code::ERROR, $e->getCode());
    }

    public function testCarriesStructuredErrors(): void
    {
        $error = new JsonSchemaError('age', '/age', 'min', new ConstraintViolation('minimum', ['minimum' => 20]));
        $e = new JsonSchemaResponseException('m', Code::ERROR, new JsonSchemaErrors([$error]));

        $this->assertCount(1, $e->getErrors());
    }
}
