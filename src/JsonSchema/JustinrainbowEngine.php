<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use BEAR\Resource\Types;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Override;
use stdClass;

use function is_array;
use function is_object;

/**
 * Default engine: justinrainbow/json-schema with type casting.
 *
 * @psalm-import-type Body from Types
 * @psalm-import-type JsonSchemaValidatorErrors from Types
 */
final class JustinrainbowEngine implements JsonSchemaEngineInterface
{
    /** {@inheritDoc} */
    #[Override]
    public function validateRequest(array $arguments, string $schemaFile): array
    {
        return $this->validate($arguments, $schemaFile);
    }

    /** {@inheritDoc} */
    #[Override]
    public function validateResponse(array|stdClass $body, string $schemaFile): array
    {
        return $this->validate($body, $schemaFile);
    }

    /**
     * @param array<mixed>|stdClass $target
     *
     * @return JsonSchemaValidatorErrors
     */
    private function validate(array|stdClass $target, string $schemaFile): array
    {
        $validator = new Validator();
        $schema = (object) ['$ref' => 'file://' . $schemaFile];
        $scanArray = is_array($target) ? $target : $this->deepArray($target);
        $validator->validate($scanArray, $schema, Constraint::CHECK_MODE_TYPE_CAST);

        /** @var JsonSchemaValidatorErrors $errors */
        $errors = $validator->isValid() ? [] : $validator->getErrors();

        return $errors;
    }

    /** @return Body */
    private function deepArray(object $values): array
    {
        $result = [];
        // Iterating over object properties yields mixed key/value types
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $key => $value) { // @phpstan-ignore-line
            /** @psalm-suppress MixedArrayOffset */
            $result[$key] = is_object($value) ? $this->deepArray($value) : $value; // @phpstan-ignore-line
        }

        return $result;
    }
}
