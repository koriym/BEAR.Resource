<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use ArgumentCountError;
use BEAR\Resource\Types;
use stdClass;

use function array_map;
use function array_pop;
use function array_values;
use function count;
use function ctype_digit;
use function end;
use function get_object_vars;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use function json_encode;
use function property_exists;
use function sprintf;
use function str_starts_with;
use function strrpos;
use function substr;
use function trim;
use function ucfirst;
use function vsprintf;

/**
 * Converts Corvus `Detailed` result rows into justinrainbow-shaped error rows.
 *
 * Corvus reports no parameters, so they are recovered from the schema node the
 * row's evaluation location points at (following `$ref`) and from the data.
 *
 * @internal
 * @psalm-import-type JsonSchemaValidatorErrors from Types
 * @psalm-suppress MixedAssignment, RawObjectIteration Schema nodes and data are decoded JSON; shapes are checked at the use sites.
 * @psalm-type Row = array{isMatch: bool, message: string, evaluationLocation: string, schemaLocation: string, instanceLocation: string}
 */
final class CorvusErrorMapper
{
    private const WRAPPER_MESSAGE = 'The value was expected to match the subschema.';

    /** Keywords whose sub-results are alternatives, not errors of their own. */
    private const OPAQUE = ['anyOf', 'oneOf', 'not', 'contains', 'if'];

    /** Parents whose children are names, not keywords. */
    private const NAME_CONTAINERS = ['properties', 'patternProperties', 'definitions', '$defs', 'dependentSchemas', 'dependencies'];

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

    /** Message templates follow justinrainbow's so `message` reads the same on both engines. */
    private const MESSAGES = [
        'additionalProp' => 'The property %s is not defined and the definition does not allow additional properties',
        'additionalItems' => 'The item %s[%s] is not defined and the definition does not allow additional items',
        'allOf' => 'Failed to match all schemas',
        'anyOf' => 'Failed to match at least one schema',
        'oneOf' => 'Failed to match exactly one schema',
        'not' => 'Matched a schema which it should not',
        'contains' => 'Does not have a value valid to contains schema',
        'const' => 'Does not have a value equal to %s',
        'enum' => 'Does not have a value in the enumeration %s',
        'dependencies' => '%s depends on %s, which is missing',
        'false' => 'Boolean schema false',
        'multipleOf' => 'Must be a multiple of %s',
        'minimum' => 'Must have a minimum value greater than or equal to %s',
        'maximum' => 'Must have a maximum value less than or equal to %s',
        'exclusiveMinimum' => 'Must have a minimum value greater than %s',
        'exclusiveMaximum' => 'Must have a maximum value less than %s',
        'minLength' => 'Must be at least %s characters long',
        'maxLength' => 'Must be at most %s characters long',
        'pattern' => 'Does not match the regex pattern %s',
        'minItems' => 'There must be a minimum of %s items in the array, %s found',
        'maxItems' => 'There must be a maximum of %s items in the array, %s found',
        'uniqueItems' => 'There are no duplicates allowed in the array',
        'minProperties' => 'Must contain a minimum of %s properties',
        'maxProperties' => 'Must contain no more than %s properties',
        'required' => 'The property %s is required',
        'unevaluatedProperties' => 'The property %s is not evaluated by any schema and unevaluated properties are not allowed',
        'type' => '%s value found, but %s is required',
        'dateFormat' => 'Invalid date %s, expected format YYYY-MM-DD',
        'timeFormat' => 'Invalid time %s, expected format hh:mm:ss',
        'dateTimeFormat' => 'Invalid date-time %s, expected format YYYY-MM-DDThh:mm:ssZ or YYYY-MM-DDThh:mm:ss+hh:mm',
        'regexFormat' => 'Invalid regex format %s',
        'urlFormat' => 'Invalid URL format',
        'emailFormat' => 'Invalid email',
        'ipFormat' => 'Invalid IP address',
        'styleHostName' => 'Invalid hostname',
        'durationFormat' => 'Invalid duration %s, expected ISO 8601 duration such as P1DT12H',
        'uuidFormat' => 'Invalid UUID format',
        'jsonPointerFormat' => 'Invalid JSON pointer',
        'uriTemplateFormat' => 'Invalid URI template format',
    ];

    public function __construct(private SchemaDocuments $documents)
    {
    }

    /**
     * @param list<Row> $rows
     *
     * @return JsonSchemaValidatorErrors
     */
    public function map(array $rows, string $schemaPath, mixed $data): array
    {
        $errors = [];
        $required = [];
        foreach ($this->leaves($rows) as $row) {
            $error = $this->toError($row, $schemaPath, $data);
            /** @var array{name: string, params: array<string, mixed>} $constraint */
            $constraint = $error['constraint'];
            // keyword-level summary of unevaluatedProperties; the per-property rows carry the names
            if ($constraint['name'] === 'unevaluatedProperties' && ($constraint['params']['property'] ?? null) === '') {
                continue;
            }

            // justinrainbow lists missing properties before the checks of the properties present
            if ($constraint['name'] === 'required') {
                $required[] = $error;
            } else {
                $errors[] = $error;
            }
        }

        $errors = [...$required, ...$errors];

        // The instance is invalid but no leaf survived (e.g. a boolean `false` schema).
        return $errors !== [] ? $errors : [$this->row('', 'unknown', [], 'The value does not match the schema')];
    }

    /**
     * Keep the failing rows that carry the actual keyword failure: drop
     * subschema wrapper rows, rows inside anyOf/oneOf/not/contains/if, and
     * rows that have a failing descendant.
     *
     * @param list<Row> $rows
     *
     * @return list<Row>
     */
    private function leaves(array $rows): array
    {
        $opaque = [];
        foreach ($rows as $row) {
            if ($row['isMatch'] || ! $this->isOpaque($row['evaluationLocation'])) {
                continue;
            }

            $opaque[] = $row['evaluationLocation'] . '/';
        }

        $kept = [];
        foreach ($rows as $row) {
            $location = $row['evaluationLocation'];
            if ($row['isMatch'] || $location === '' || $row['message'] === self::WRAPPER_MESSAGE || $this->isInside($location, $opaque)) {
                continue;
            }

            $kept[$location . '|' . $row['instanceLocation']] = $row;
        }

        $locations = array_map(static fn (array $row): string => $row['evaluationLocation'], $kept);
        $leaves = [];
        foreach ($kept as $row) {
            foreach ($locations as $other) {
                if (str_starts_with($other, $row['evaluationLocation'] . '/')) {
                    continue 2;
                }
            }

            $leaves[] = $row;
        }

        return $leaves;
    }

    private function isOpaque(string $location): bool
    {
        $segments = $this->documents->segments($location);
        $keyword = array_pop($segments);

        return in_array($keyword, self::OPAQUE, true) && ! in_array(end($segments), self::NAME_CONTAINERS, true);
    }

    /** @param list<string> $prefixes */
    private function isInside(string $location, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($location, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Row $row
     *
     * @return array<string, mixed>
     */
    private function toError(array $row, string $schemaPath, mixed $data): array
    {
        $located = $this->documents->locate($schemaPath, $row['evaluationLocation']);
        $schema = $located[0] ?? new stdClass();
        $keyword = $located[1] ?? $this->lastSegment($row['evaluationLocation']);
        $pointer = $row['instanceLocation'];
        $value = $this->valueAt($data, $this->documents->segments($pointer));

        if ($keyword === 'required') {
            return $this->row($pointer, 'required', ['property' => $this->lastSegment($pointer)]);
        }

        if ($keyword === 'additionalProperties' || $keyword === 'unevaluatedProperties') {
            $name = $keyword === 'additionalProperties' ? 'additionalProp' : 'unevaluatedProperties';

            return $this->row($this->parent($pointer), $name, ['property' => $this->lastSegment($pointer)]);
        }

        if ($keyword === 'additionalItems' || ($keyword === 'items' && ($schema->items ?? null) === false)) {
            $parent = $this->parent($pointer);
            $params = ['item' => $this->property($parent), 'property' => (int) $this->lastSegment($pointer), 'additionalItems' => false];

            return $this->row($parent, 'additionalItems', $params);
        }

        if (in_array($keyword, ['dependencies', 'dependentRequired'], true)) {
            return $this->dependency($schema->{$keyword} ?? null, $pointer, $data);
        }

        [$name, $params] = $this->constraint($keyword, $schema, $value);

        return $this->row($pointer, $name, $params, $row['message']);
    }

    /**
     * Corvus reports the missing dependent property; justinrainbow reports the
     * property that demands it, at the parent.
     *
     * @return array<string, mixed>
     */
    private function dependency(mixed $declared, string $pointer, mixed $data): array
    {
        $missing = $this->lastSegment($pointer);
        $parent = $this->parent($pointer);
        $params = [];
        if ($declared instanceof stdClass) {
            $owner = $this->valueAt($data, $this->documents->segments($parent));
            /** @psalm-suppress MixedAssignment */
            foreach (get_object_vars($declared) as $key => $required) {
                if (is_array($required) && in_array($missing, $required, true) && $owner instanceof stdClass && property_exists($owner, $key)) {
                    $params = ['key' => $key, 'dependency' => $required];
                    break;
                }
            }
        }

        return $this->row($parent, 'dependencies', $params);
    }

    /** @return array{string, array<string, mixed>} */
    private function constraint(string $keyword, stdClass $schema, mixed $value): array
    {
        /** @psalm-suppress MixedAssignment */
        $declared = $schema->{$keyword} ?? null;

        switch ($keyword) {
            case 'minimum':
            case 'maximum':
                $exclusive = 'exclusive' . ucfirst($keyword);

                return [($schema->{$exclusive} ?? false) === true ? $exclusive : $keyword, [$keyword => $declared]];

            case 'exclusiveMinimum':
            case 'exclusiveMaximum':
                $bound = $keyword === 'exclusiveMinimum' ? 'minimum' : 'maximum';

                return [$keyword, [$bound => is_bool($declared) ? ($schema->{$bound} ?? null) : $declared]];

            case 'uniqueItems':
                return ['uniqueItems', []];

            case 'multipleOf':
            case 'minLength':
            case 'maxLength':
            case 'pattern':
            case 'enum':
            case 'const':
            case 'minProperties':
            case 'maxProperties':
            case 'minContains':
            case 'maxContains':
                return [$keyword, [$keyword => $declared]];

            case 'minItems':
            case 'maxItems':
                return [$keyword, [$keyword => $declared, 'found' => is_array($value) ? count($value) : null]];

            case 'type':
                return ['type', ['found' => $this->found($value), 'expected' => $this->expected($declared)]];

            case 'format':
                return $this->format(is_string($declared) ? $declared : '', $value);

            default:
                if ($declared === false) {
                    return ['false', []];
                }

                return [$keyword, is_scalar($declared) ? [$keyword => $declared] : []];
        }
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

    /** PHP type name; objects are 'object' (justinrainbow reports them as 'array'). */
    private function found(mixed $value): string
    {
        return gettype($value);
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

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function row(string $pointer, string $name, array $params, string|null $fallbackMessage = null): array
    {
        return [
            'property' => $this->property($pointer),
            'pointer' => $pointer,
            'message' => $this->message($name, $params, $fallbackMessage),
            'constraint' => ['name' => $name, 'params' => $params],
        ];
    }

    /** @param array<string, mixed> $params */
    private function message(string $name, array $params, string|null $fallback): string
    {
        if (! isset(self::MESSAGES[$name])) {
            return $fallback ?? 'The value does not match the schema';
        }

        $args = array_map(
            static fn (mixed $value): string|int|float => is_bool($value) ? ($value ? 'true' : 'false') : (is_string($value) || is_int($value) || is_float($value) ? $value : (string) json_encode($value)),
            array_values($params),
        );

        try {
            return ucfirst(vsprintf(self::MESSAGES[$name], $args));
        } catch (ArgumentCountError) {
            return $fallback ?? 'The value does not match the schema';
        }
    }

    /** `/n/x` => `n.x`, `/arr/2` => `arr[2]` (justinrainbow's property path). */
    private function property(string $pointer): string
    {
        $path = '';
        foreach ($this->documents->segments($pointer) as $segment) {
            $path .= ctype_digit($segment) ? sprintf('[%d]', $segment) : '.' . $segment;
        }

        return trim($path, '.');
    }

    private function parent(string $pointer): string
    {
        $position = strrpos($pointer, '/');

        return $position === false ? '' : substr($pointer, 0, $position);
    }

    private function lastSegment(string $pointer): string
    {
        $segments = $this->documents->segments($pointer);

        return $segments === [] ? '' : end($segments);
    }

    /** @param list<string> $segments */
    private function valueAt(mixed $data, array $segments): mixed
    {
        foreach ($segments as $segment) {
            if ($data instanceof stdClass) {
                $data = $data->{$segment} ?? null;
            } elseif (is_array($data)) {
                $data = $data[$segment] ?? null;
            } else {
                return null;
            }
        }

        return $data;
    }
}
