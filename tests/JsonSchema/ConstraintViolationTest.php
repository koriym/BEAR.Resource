<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use PHPUnit\Framework\TestCase;

class ConstraintViolationTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $violation = new ConstraintViolation('type', ['expected' => 'integer', 'found' => 'string']);
        $this->assertSame('type', $violation->name);
        $this->assertSame(['expected' => 'integer', 'found' => 'string'], $violation->params);
    }

    public function testAcceptsNonSpecConstraintName(): void
    {
        $violation = new ConstraintViolation('futureKeyword', []);
        $this->assertSame('futureKeyword', $violation->name);
        $this->assertSame([], $violation->params);
    }
}
