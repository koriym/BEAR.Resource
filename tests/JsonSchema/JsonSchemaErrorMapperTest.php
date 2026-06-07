<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function json_decode;

use const JSON_THROW_ON_ERROR;

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
        $this->assertSame('Must have a minimum value of 20', $error->rawMessage);
        $this->assertSame('minimum', $error->constraint->name);
        $this->assertSame(['minimum' => 20], $error->constraint->params);
    }

    public function testAppliesPropertyErrorMessageFromSchema(): void
    {
        $row = [
            'property' => 'age',
            'pointer' => '/age',
            'message' => 'Must have a minimum value of 20',
            'constraint' => ['name' => 'minimum', 'params' => ['minimum' => 20]],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row, $this->schema(<<<'JSON'
{
  "type": "object",
  "properties": {
    "age": {
      "type": "integer",
      "minimum": 20,
      "errorMessage": {"minimum": "Age must be at least {minimum}."}
    }
  }
}
JSON));

        $this->assertSame('Age must be at least 20.', $error->message);
        $this->assertSame('Must have a minimum value of 20', $error->rawMessage);
    }

    public function testAppliesRequiredErrorMessageFromSchema(): void
    {
        $row = [
            'property' => '',
            'pointer' => '',
            'message' => 'The property name is required',
            'constraint' => ['name' => 'required', 'params' => ['property' => 'name']],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row, $this->schema(<<<'JSON'
{
  "type": "object",
  "required": ["name"],
  "errorMessage": {
    "required": {
      "name": "Name is required."
    }
  },
  "properties": {
    "name": {"type": "string"}
  }
}
JSON));

        $this->assertSame('name', $error->property);
        $this->assertSame('Name is required.', $error->message);
        $this->assertSame('The property name is required', $error->rawMessage);
    }

    public function testRequiredConstraintWithEmptyMissingPropertyFallsBackToRowProperty(): void
    {
        $row = [
            'property' => 'fallback',
            'pointer' => '',
            'message' => 'The property is required',
            'constraint' => ['name' => 'required', 'params' => ['property' => '']],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row);

        $this->assertSame('fallback', $error->property);
    }

    public function testMapsJustinrainbow5xStringConstraintShape(): void
    {
        // In justinrainbow 5.x, `constraint` is a string and per-keyword params
        // (e.g. `minimum`, `maxLength`) are flattened onto the parent row.
        $row = [
            'property' => 'age',
            'pointer' => '/age',
            'message' => 'Must have a minimum value of 20',
            'constraint' => 'minimum',
            'minimum' => 20,
            'context' => 1,
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row);

        $this->assertSame('minimum', $error->constraint->name);
        // Flattened param is recovered; standard row keys (context, etc.) are excluded.
        $this->assertSame(['minimum' => 20], $error->constraint->params);
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

    public function testResolvesNestedSchemaMessageByPointer(): void
    {
        $row = [
            'property' => 'users[0].name',
            'pointer' => '/users/0/name',
            'message' => 'Must be at least 3 chars',
            'constraint' => ['name' => 'minLength', 'params' => ['minLength' => 3]],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row, $this->schema(<<<'JSON'
{
  "type": "object",
  "properties": {
    "users": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "name": {
            "type": "string",
            "minLength": 3,
            "errorMessage": {"minLength": "name must be at least {minLength} chars"}
          }
        }
      }
    }
  }
}
JSON));

        $this->assertSame('name must be at least 3 chars', $error->message);
        $this->assertSame('Must be at least 3 chars', $error->rawMessage);
        $this->assertTrue($error->isCustomMessage);
    }

    public function testIsCustomMessageFalseWhenNoSchemaOverride(): void
    {
        $row = [
            'property' => 'age',
            'pointer' => '/age',
            'message' => 'Must have a minimum value of 20',
            'constraint' => ['name' => 'minimum', 'params' => ['minimum' => 20]],
        ];

        $error = (new JsonSchemaErrorMapper())->toJsonSchemaError($row, $this->schema('{"properties": {"age": {"type": "integer"}}}'));

        $this->assertFalse($error->isCustomMessage);
        $this->assertSame($error->rawMessage, $error->message);
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

    private function schema(string $json): stdClass
    {
        $schema = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        assert($schema instanceof stdClass);

        return $schema;
    }
}
