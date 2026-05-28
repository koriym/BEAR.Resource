<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\Exception\JsonSchemaRequestException;
use BEAR\Resource\JsonSchema\ConstraintViolation;
use BEAR\Resource\JsonSchema\JsonSchemaError;
use BEAR\Resource\JsonSchema\JsonSchemaErrors;
use PHPUnit\Framework\TestCase;

class JsonSchemaRequestExceptionTest extends TestCase
{
    public function testExtendsJsonSchemaException(): void
    {
        $e = new JsonSchemaRequestException('bad input', Code::BAD_REQUEST);

        $this->assertInstanceOf(JsonSchemaException::class, $e);
        $this->assertSame('bad input', $e->getMessage());
        $this->assertSame(Code::BAD_REQUEST, $e->getCode());
    }

    public function testCarriesStructuredErrors(): void
    {
        $error = new JsonSchemaError('age', '/age', 'min', new ConstraintViolation('minimum', ['minimum' => 20]));
        $e = new JsonSchemaRequestException('m', Code::BAD_REQUEST, new JsonSchemaErrors([$error]));

        $this->assertCount(1, $e->getErrors());
    }
}
