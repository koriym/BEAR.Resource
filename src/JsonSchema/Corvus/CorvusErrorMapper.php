<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\Types;
use stdClass;

use function array_filter;
use function get_object_vars;
use function in_array;
use function is_array;
use function property_exists;

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
    private ResultLeaves $leaves;
    private ConstraintParams $params;

    public function __construct(private SchemaDocuments $documents)
    {
        $this->leaves = new ResultLeaves();
        $this->params = new ConstraintParams();
    }

    /**
     * @param list<Row> $rows
     *
     * @return JsonSchemaValidatorErrors
     */
    public function map(array $rows, string $schemaPath, mixed $data): array
    {
        $errors = [];
        foreach ($this->leaves->of($rows) as $row) {
            $error = $this->toError($row, $schemaPath, $data);
            /** @var array{name: string, params: array<string, mixed>} $constraint */
            $constraint = $error['constraint'];
            // keyword-level summary of unevaluatedProperties; the per-property rows carry the names
            if ($constraint['name'] === 'unevaluatedProperties' && ($constraint['params']['property'] ?? null) === '') {
                continue;
            }

            $errors[] = $error;
        }

        if ($errors === []) {
            // The instance is invalid but no leaf survived (e.g. a boolean `false` schema).
            return [ErrorRow::build('', 'unknown', [])];
        }

        // justinrainbow lists missing properties before the checks of the properties present
        $isRequired = static fn (array $error): bool => ($error['constraint']['name'] ?? null) === 'required';

        return [...array_filter($errors, $isRequired), ...array_filter($errors, static fn (array $error): bool => ! $isRequired($error))];
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
        $keyword = $located[1] ?? JsonPointer::last($row['evaluationLocation']);
        $pointer = $row['instanceLocation'];

        if ($keyword === 'required') {
            return ErrorRow::build($pointer, 'required', ['property' => JsonPointer::last($pointer)]);
        }

        if ($keyword === 'additionalProperties' || $keyword === 'unevaluatedProperties') {
            $name = $keyword === 'additionalProperties' ? 'additionalProp' : 'unevaluatedProperties';

            return ErrorRow::build(JsonPointer::parent($pointer), $name, ['property' => JsonPointer::last($pointer)]);
        }

        if ($keyword === 'additionalItems' || ($keyword === 'items' && ($schema->items ?? null) === false)) {
            $parent = JsonPointer::parent($pointer);
            $params = ['item' => JsonPointer::property($parent), 'property' => (int) JsonPointer::last($pointer), 'additionalItems' => false];

            return ErrorRow::build($parent, 'additionalItems', $params);
        }

        if (in_array($keyword, ['dependencies', 'dependentRequired'], true)) {
            return $this->dependency($schema->{$keyword} ?? null, $pointer, $data);
        }

        [$name, $params] = $this->params->of($keyword, $schema, JsonPointer::valueAt($data, $pointer));

        return ErrorRow::build($pointer, $name, $params, $row['message']);
    }

    /**
     * Corvus reports the missing dependent property; justinrainbow reports the
     * property that demands it, at the parent.
     *
     * @return array<string, mixed>
     */
    private function dependency(mixed $declared, string $pointer, mixed $data): array
    {
        $missing = JsonPointer::last($pointer);
        $parent = JsonPointer::parent($pointer);
        $params = [];
        if ($declared instanceof stdClass) {
            $owner = JsonPointer::valueAt($data, $parent);
            /** @psalm-suppress MixedAssignment */
            foreach (get_object_vars($declared) as $key => $required) {
                if (is_array($required) && in_array($missing, $required, true) && $owner instanceof stdClass && property_exists($owner, $key)) {
                    $params = ['key' => $key, 'dependency' => $required];
                    break;
                }
            }
        }

        return ErrorRow::build($parent, 'dependencies', $params);
    }
}
