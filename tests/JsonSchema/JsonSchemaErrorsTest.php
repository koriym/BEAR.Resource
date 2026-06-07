<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;

use function array_keys;
use function count;
use function iterator_to_array;

class JsonSchemaErrorsTest extends TestCase
{
    public function testEmptyCollection(): void
    {
        $errors = new JsonSchemaErrors([]);

        $this->assertFalse($errors->hasErrors());
        $this->assertSame(0, $errors->count());
        $this->assertSame([], iterator_to_array($errors));
        $this->assertSame([], $errors->byProperty());
        $this->assertNull($errors->first());
    }

    public function testFirstReturnsTheLeadingError(): void
    {
        $a = new JsonSchemaError('age', '/age', 'minimum is 20', new ConstraintViolation('minimum', []));
        $b = new JsonSchemaError('name', '/name', 'is required', new ConstraintViolation('required', []));
        $first = (new JsonSchemaErrors([$a, $b]))->first();

        $this->assertSame($a, $first);
    }

    public function testIteratesAndCounts(): void
    {
        $a = new JsonSchemaError('name', '/name', 'is required', new ConstraintViolation('required', []));
        $b = new JsonSchemaError('age', '/age', 'minimum is 20', new ConstraintViolation('minimum', ['minimum' => 20]));
        $errors = new JsonSchemaErrors([$a, $b]);

        $this->assertTrue($errors->hasErrors());
        $this->assertCount(2, $errors);
        $this->assertSame(2, count($errors));
        $this->assertSame([$a, $b], iterator_to_array($errors));
    }

    public function testGetIteratorCanBeConsumedDirectly(): void
    {
        $error = new JsonSchemaError('name', '/name', 'is required', new ConstraintViolation('required', []));

        $this->assertSame([$error], iterator_to_array((new JsonSchemaErrors([$error]))->getIterator()));
    }

    public function testByPropertyGroupsErrors(): void
    {
        $first = new JsonSchemaError('age', '/age', 'minimum is 20', new ConstraintViolation('minimum', []));
        $second = new JsonSchemaError('age', '/age', 'must be integer', new ConstraintViolation('type', []));
        $third = new JsonSchemaError('name', '/name', 'is required', new ConstraintViolation('required', []));

        $grouped = (new JsonSchemaErrors([$first, $second, $third]))->byProperty();

        $this->assertSame(['age', 'name'], array_keys($grouped));
        $this->assertSame([$first, $second], $grouped['age']);
        $this->assertSame([$third], $grouped['name']);
    }

    public function testCombinedMessageReturnsEmptyStringForEmptyCollection(): void
    {
        $this->assertSame('', (new JsonSchemaErrors([]))->format());
    }

    public function testCombinedMessageDefaultTemplateJoinsMessages(): void
    {
        $a = new JsonSchemaError('age', '/age', 'minimum is 20', new ConstraintViolation('minimum', []));
        $b = new JsonSchemaError('name', '/name', 'is required', new ConstraintViolation('required', []));

        $this->assertSame("minimum is 20\nis required\n", (new JsonSchemaErrors([$a, $b]))->format());
    }

    public function testCombinedMessageInterpolatesCustomTemplate(): void
    {
        $a = new JsonSchemaError('age', '/age', 'minimum is 20', new ConstraintViolation('minimum', ['minimum' => 20]));
        $b = new JsonSchemaError('name', '/name', 'is required', new ConstraintViolation('required', []));

        $this->assertSame(
            "<li>age: minimum is 20</li>\n<li>name: is required</li>\n",
            (new JsonSchemaErrors([$a, $b]))->format("<li>{property}: {message}</li>\n"),
        );
    }
}
