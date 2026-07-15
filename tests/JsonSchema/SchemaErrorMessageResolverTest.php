<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class SchemaErrorMessageResolverTest extends TestCase
{
    private SchemaErrorMessageResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SchemaErrorMessageResolver();
    }

    public function testReturnsNullWhenSchemaIsNull(): void
    {
        $error = $this->error('/age', 'minimum', 'age');
        $this->assertNull($this->resolver->resolve(null, $error));
    }

    public function testReturnsNullWhenPointerDoesNotResolve(): void
    {
        $error = $this->error('/unknown', 'minimum', 'unknown');
        $this->assertNull($this->resolver->resolve($this->schema('{"properties": {"age": {}}}'), $error));
    }

    public function testReturnsNullWhenPropertyHasNoErrorMessage(): void
    {
        $error = $this->error('/age', 'minimum', 'age');
        $this->assertNull($this->resolver->resolve($this->schema('{"properties": {"age": {"minimum": 20}}}'), $error));
    }

    public function testConstraintMessageAsString(): void
    {
        $error = $this->error('/age', 'minimum', 'age');
        $schema = $this->schema('{"properties": {"age": {"errorMessage": "always this message"}}}');
        $this->assertSame('always this message', $this->resolver->resolve($schema, $error));
    }

    public function testConstraintMessageObjectMissingConstraintKey(): void
    {
        $error = $this->error('/age', 'maximum', 'age');
        $schema = $this->schema('{"properties": {"age": {"errorMessage": {"minimum": "x"}}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testConstraintMessageObjectKeyNotString(): void
    {
        $error = $this->error('/age', 'minimum', 'age');
        $schema = $this->schema('{"properties": {"age": {"errorMessage": {"minimum": 42}}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testRequiredMessageAsString(): void
    {
        $error = $this->error('', 'required', 'name');
        $schema = $this->schema('{"errorMessage": {"required": "global required template"}, "properties": {"name": {}}}');
        $this->assertSame('global required template', $this->resolver->resolve($schema, $error));
    }

    public function testRequiredMessagePerProperty(): void
    {
        $error = $this->error('', 'required', 'name');
        $schema = $this->schema('{"errorMessage": {"required": {"name": "Name required"}}, "properties": {"name": {}}}');
        $this->assertSame('Name required', $this->resolver->resolve($schema, $error));
    }

    public function testRequiredMessagePerPropertyIgnoresNonStringMessage(): void
    {
        $error = $this->error('', 'required', 'name');
        $schema = $this->schema('{"errorMessage": {"required": {"name": 42}}, "properties": {"name": {}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testRequiredMessageMissingPropertyKey(): void
    {
        $error = $this->error('', 'required', 'email');
        $schema = $this->schema('{"errorMessage": {"required": {"name": "Name required"}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testRequiredMessageWithoutErrorMessage(): void
    {
        $error = $this->error('', 'required', 'name');
        $this->assertNull($this->resolver->resolve($this->schema('{"required": ["name"]}'), $error));
    }

    public function testRequiredMessageWithoutRequiredKey(): void
    {
        // errorMessage is declared but covers a different constraint, not `required`.
        $error = $this->error('', 'required', 'name');
        $schema = $this->schema('{"errorMessage": {"type": "wrong type"}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testDescendObjectWhenNodeHasNoProperties(): void
    {
        // After descending to a leaf scalar, a further path segment can't go anywhere.
        $error = $this->error('/age/sub', 'minimum', 'age.sub');
        $schema = $this->schema('{"properties": {"age": {"type": "integer"}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testDescendArrayWhenItemsIsNeitherSchemaNorTuple(): void
    {
        // `items: false` (forbid additional items) — neither stdClass nor array.
        $error = $this->error('/list/0', 'type', 'list[0]');
        $schema = $this->schema('{"properties": {"list": {"type": "array", "items": false}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testTupleItemsArray(): void
    {
        // For tuple-form `items` ([schemaA, schemaB]), the numeric pointer segment
        // selects the schema at that index.
        $error = $this->error('/pair/1', 'minimum', 'pair[1]');
        $schema = $this->schema(<<<'JSON'
{
  "properties": {
    "pair": {
      "type": "array",
      "items": [
        {"type": "string"},
        {"type": "integer", "errorMessage": {"minimum": "second must be >= {minimum}"}}
      ]
    }
  }
}
JSON);

        $this->assertSame('second must be >= {minimum}', $this->resolver->resolve($schema, $error));
    }

    public function testTupleItemsArrayIgnoresNonSchemaEntry(): void
    {
        $error = $this->error('/pair/0', 'minimum', 'pair[0]');
        $schema = $this->schema('{"properties": {"pair": {"type": "array", "items": [false]}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testNumericSegmentWithoutItems(): void
    {
        $error = $this->error('/age/0', 'minimum', 'age[0]');
        // age is an integer, not an array — no `items` to descend into.
        $schema = $this->schema('{"properties": {"age": {"type": "integer"}}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testObjectPropertyThatIsNotSchemaCannotBeResolved(): void
    {
        $error = $this->error('/age', 'minimum', 'age');
        $schema = $this->schema('{"properties": {"age": false}}');
        $this->assertNull($this->resolver->resolve($schema, $error));
    }

    public function testRfc6901EscapedSegments(): void
    {
        $error = $this->error('/foo~1bar', 'minLength', 'foo/bar');
        $schema = $this->schema('{"properties": {"foo/bar": {"errorMessage": {"minLength": "ok"}}}}');
        $this->assertSame('ok', $this->resolver->resolve($schema, $error));
    }

    private function error(string $pointer, string $constraintName, string $property): JsonSchemaError
    {
        return new JsonSchemaError($property, $pointer, 'orig', new ConstraintViolation($constraintName, []));
    }

    private function schema(string $json): stdClass
    {
        $schema = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        assert($schema instanceof stdClass);

        return $schema;
    }
}
