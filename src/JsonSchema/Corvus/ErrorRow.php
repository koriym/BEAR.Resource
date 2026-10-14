<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use ArgumentCountError;

use function array_map;
use function array_values;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_encode;
use function ucfirst;
use function vsprintf;

/**
 * Builds a justinrainbow-shaped error row.
 *
 * @internal
 */
final class ErrorRow
{
    public const FALLBACK_MESSAGE = 'The value does not match the schema';

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

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public static function build(string $pointer, string $name, array $params, string|null $fallbackMessage = null): array
    {
        return [
            'property' => JsonPointer::property($pointer),
            'pointer' => $pointer,
            'message' => self::message($name, $params, $fallbackMessage ?? self::FALLBACK_MESSAGE),
            'constraint' => ['name' => $name, 'params' => $params],
        ];
    }

    /** @param array<string, mixed> $params */
    private static function message(string $name, array $params, string $fallback): string
    {
        if (! isset(self::MESSAGES[$name])) {
            return $fallback;
        }

        $args = array_map(
            static fn (mixed $value): string|int|float => is_bool($value) ? ($value ? 'true' : 'false') : (is_string($value) || is_int($value) || is_float($value) ? $value : (string) json_encode($value)),
            array_values($params),
        );

        try {
            return ucfirst(vsprintf(self::MESSAGES[$name], $args));
        } catch (ArgumentCountError) {
            return $fallback;
        }
    }
}
