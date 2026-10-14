<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use stdClass;

use function array_pop;
use function count;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function json_encode;
use function ucfirst;

/**
 * Recovers justinrainbow's constraint name and params for a failed keyword
 * from the schema node that owns it and the value that failed.
 *
 * @internal
 * @psalm-suppress MixedAssignment Schema nodes are decoded JSON; shapes are checked at the use sites.
 */
final class ConstraintParams
{
    /** Keywords whose only param is their declared value. */
    private const DECLARED = ['multipleOf', 'minLength', 'maxLength', 'pattern', 'enum', 'const', 'minProperties', 'maxProperties', 'minContains', 'maxContains'];

    private const WORDING = [
        'integer' => 'an integer',
        'number' => 'a number',
        'boolean' => 'a boolean',
        'object' => 'an object',
        'array' => 'an array',
        'string' => 'a string',
        'null' => 'a null',
    ];

    /** format => [constraint name, params carrying the value (key => encode?)] */
    private const FORMATS = [
        'date' => ['dateFormat', 'date', false],
        'time' => ['timeFormat', 'time', true],
        'date-time' => ['dateTimeFormat', 'dateTime', true],
        'regex' => ['regexFormat', 'value', false],
        'uri' => ['urlFormat', null, false],
        'uri-reference' => ['urlFormat', null, false],
        'email' => ['emailFormat', null, false],
        'idn-email' => ['emailFormat', null, false],
        'ipv4' => ['ipFormat', null, false],
        'ipv6' => ['ipFormat', null, false],
        'hostname' => ['styleHostName', null, false],
        'duration' => ['durationFormat', null, false],
        'uuid' => ['uuidFormat', null, false],
        'json-pointer' => ['jsonPointerFormat', null, false],
        'uri-template' => ['uriTemplateFormat', null, false],
    ];

    /** @return array{string, array<string, mixed>} [constraint name, params] */
    public function of(string $keyword, stdClass $schema, mixed $value): array
    {
        $declared = $schema->{$keyword} ?? null;
        if (in_array($keyword, self::DECLARED, true)) {
            return [$keyword, [$keyword => $declared]];
        }

        return match ($keyword) {
            'minimum', 'maximum' => $this->bound($keyword, $schema, $declared),
            'exclusiveMinimum', 'exclusiveMaximum' => $this->exclusiveBound($keyword, $schema, $declared),
            'uniqueItems' => ['uniqueItems', []],
            'minItems', 'maxItems' => [$keyword, [$keyword => $declared, 'found' => is_array($value) ? count($value) : null]],
            'type' => ['type', ['found' => gettype($value), 'expected' => $this->expected($declared)]],
            'format' => $this->format(is_string($declared) ? $declared : '', $value),
            default => $this->generic($keyword, $declared),
        };
    }

    /**
     * Draft-04 spells an exclusive bound as `minimum` plus `exclusiveMinimum: true`.
     *
     * @return array{string, array<string, mixed>}
     */
    private function bound(string $keyword, stdClass $schema, mixed $declared): array
    {
        $exclusive = 'exclusive' . ucfirst($keyword);

        return [($schema->{$exclusive} ?? false) === true ? $exclusive : $keyword, [$keyword => $declared]];
    }

    /** @return array{string, array<string, mixed>} */
    private function exclusiveBound(string $keyword, stdClass $schema, mixed $declared): array
    {
        $bound = $keyword === 'exclusiveMinimum' ? 'minimum' : 'maximum';

        return [$keyword, [$bound => is_bool($declared) ? ($schema->{$bound} ?? null) : $declared]];
    }

    /** @return array{string, array<string, mixed>} */
    private function generic(string $keyword, mixed $declared): array
    {
        if ($declared === false) {
            return ['false', []];
        }

        return [$keyword, is_scalar($declared) ? [$keyword => $declared] : []];
    }

    /** @return array{string, array<string, mixed>} */
    private function format(string $format, mixed $value): array
    {
        if (! isset(self::FORMATS[$format])) {
            return ['format', ['format' => $format]];
        }

        [$name, $valueKey, $encode] = self::FORMATS[$format];
        $params = [];
        if ($valueKey !== null) {
            $params[$valueKey] = $encode ? json_encode($value) : $value;
        }

        $params['format'] = $format;

        return [$name, $params];
    }

    private function expected(mixed $type): string
    {
        $words = [];
        foreach (is_array($type) ? $type : [$type] as $name) {
            if (! is_string($name) || ! isset(self::WORDING[$name])) {
                continue;
            }

            $words[] = self::WORDING[$name];
        }

        if (count($words) < 2) {
            return implode('', $words);
        }

        $last = array_pop($words);

        return implode(', ', $words) . ' or ' . $last;
    }
}
