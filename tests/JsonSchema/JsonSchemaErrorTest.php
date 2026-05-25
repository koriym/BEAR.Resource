<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;

use function fopen;

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

    public function testRenderInterpolatesConstraintParam(): void
    {
        $error = new JsonSchemaError(
            'age',
            '/age',
            'Must have a minimum value of 20',
            new ConstraintViolation('minimum', ['minimum' => 20]),
        );

        $this->assertSame('年齢は20歳以上である必要があります', $error->render('年齢は{minimum}歳以上である必要があります'));
    }

    public function testRenderInterpolatesErrorFields(): void
    {
        $error = new JsonSchemaError(
            'age',
            '/age',
            'msg',
            new ConstraintViolation('minimum', []),
        );

        $this->assertSame('field=age path=/age msg=msg', $error->render('field={property} path={pointer} msg={message}'));
    }

    public function testRenderReservedKeysAreNotOverridableByConstraintParams(): void
    {
        $error = new JsonSchemaError(
            'age',
            '/age',
            'msg',
            new ConstraintViolation('minimum', ['property' => 'HIJACKED']),
        );

        $this->assertSame('age', $error->render('{property}'));
    }

    public function testRenderLeavesUnknownPlaceholdersInPlace(): void
    {
        $error = new JsonSchemaError('age', '/age', 'msg', new ConstraintViolation('minimum', []));
        $this->assertSame('unknown {foo}', $error->render('unknown {foo}'));
    }

    public function testRenderFallsBackToTypeNameForUnsupportedValues(): void
    {
        $error = new JsonSchemaError(
            'x',
            '/x',
            'msg',
            new ConstraintViolation('type', ['handle' => fopen('php://memory', 'rb')]),
        );

        $this->assertSame('resource (stream)', $error->render('{handle}'));
    }

    public function testRenderStringifiesAllPrimitiveTypes(): void
    {
        $error = new JsonSchemaError(
            'flag',
            '/flag',
            'bad',
            new ConstraintViolation('type', [
                'expected' => 'integer',
                'count' => 3,
                'enabled' => true,
                'missing' => null,
                'allowed' => ['a', 'b'],
            ]),
        );

        $this->assertSame(
            'integer 3 true null ["a","b"]',
            $error->render('{expected} {count} {enabled} {missing} {allowed}'),
        );
    }
}
