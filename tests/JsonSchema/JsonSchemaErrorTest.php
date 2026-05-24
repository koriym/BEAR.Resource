<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;

class JsonSchemaErrorTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $constraint = new ConstraintViolation('minimum', ['minimum' => 20]);
        $error = new JsonSchemaError('age', '/age', 'Must have a minimum value of 20', $constraint);

        $this->assertSame('age', $error->property);
        $this->assertSame('/age', $error->pointer);
        $this->assertSame('Must have a minimum value of 20', $error->message);
        $this->assertSame($constraint, $error->constraint);
        $this->assertSame('minimum', $error->constraint->name);
    }
}
