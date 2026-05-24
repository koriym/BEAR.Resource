<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;

class JsonSchemaErrorMapperTest extends TestCase
{
    public function testMapsJustinrainbow6xArrayConstraintShape(): void
    {
        $row = [
            'property' => 'age',
            'pointer' => '/age',
            'message' => 'Must have a minimum value of 20',
            'constraint' => ['name' => 'minimum', 'params' => ['minimum' => 20]],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row);

        $this->assertSame('age', $error->property);
        $this->assertSame('/age', $error->pointer);
        $this->assertSame('Must have a minimum value of 20', $error->message);
        $this->assertSame('minimum', $error->constraint->name);
        $this->assertSame(['minimum' => 20], $error->constraint->params);
    }

    public function testMapsJustinrainbow5xStringConstraintShape(): void
    {
        $row = [
            'property' => 'age',
            'pointer' => '/age',
            'message' => 'Must have a minimum value of 20',
            'constraint' => 'minimum',
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row);

        $this->assertSame('minimum', $error->constraint->name);
        $this->assertSame([], $error->constraint->params);
    }

    public function testFallbacksWhenConstraintIsMissing(): void
    {
        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError([]);

        $this->assertSame('', $error->property);
        $this->assertSame('', $error->pointer);
        $this->assertSame('', $error->message);
        $this->assertSame('unknown', $error->constraint->name);
        $this->assertSame([], $error->constraint->params);
    }

    public function testFallbacksWhenArrayConstraintFieldsAreInvalid(): void
    {
        $row = [
            'property' => 'age',
            'constraint' => ['name' => 42, 'params' => 'not-an-array'],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row);

        $this->assertSame('unknown', $error->constraint->name);
        $this->assertSame([], $error->constraint->params);
    }
}
