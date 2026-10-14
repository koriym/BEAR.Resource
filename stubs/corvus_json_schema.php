<?php

// Stubs mirroring the corvus_json_schema 0.1.3 extension API.

namespace Corvus\JsonSchema;

enum Dialect
{
    case Draft4;
    case Draft6;
    case Draft7;
    case Draft201909;
    case Draft202012;
}

enum ResultsLevel
{
    case Basic;
    case Detailed;
    case Verbose;
}

final class Collector
{
    public function __construct(ResultsLevel $level)
    {
    }

    /** @return list<array{isMatch: bool, message: string, evaluationLocation: string, schemaLocation: string, instanceLocation: string}> */
    public function results(): array
    {
        return [];
    }
}

final class Validator
{
    /** @param array<string, mixed> $options */
    public static function compile(mixed $schema, array $options = []): self
    {
        return new self();
    }

    public function isValid(mixed $value): bool
    {
        return true;
    }

    public function evaluate(mixed $value, Collector $collector): bool
    {
        return true;
    }
}
