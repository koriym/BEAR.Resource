<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\Exception\JsonSchemaEngineUnavailableException;
use BEAR\Resource\Exception\JsonSchemaNotFoundException;
use BEAR\Resource\JsonSchema\JsonSchemaEngineInterface;
use BEAR\Resource\Types;
use Corvus\JsonSchema\Collector;
use Corvus\JsonSchema\Dialect;
use Corvus\JsonSchema\ResultsLevel;
use Corvus\JsonSchema\Validator;
use Override;
use stdClass;

use function extension_loaded;

/**
 * Validation through the `corvus_json_schema` PHP extension (spec-correct draft-04 to 2020-12).
 *
 * Compiled validators are kept per process. An entry is reused while the schema
 * file and every file it referenced at compile time keep the same mtime and size;
 * a rewrite that keeps both (same second, same byte length) is not detected.
 *
 * The root `$schema` of each document is honoured (Corvus ignores embedded ones);
 * documents without it are compiled as draft-07.
 * Request arguments are validated as a JSON object; response bodies are
 * passed through as decoded, so `{}` and `[]` stay distinct.
 *
 * @psalm-import-type Row from CorvusErrorMapper
 * @psalm-import-type JsonSchemaValidatorErrors from Types
 * @psalm-suppress MixedAssignment, MixedArgument, PossiblyInvalidArgument, PossiblyNullArgument Schema documents are decoded JSON; shapes are checked at the use sites.
 */
final class CorvusEngine implements JsonSchemaEngineInterface
{
    /** @var array<string, array{array<string, string>, Validator}> compile-time file stamps and the validator */
    private array $validators = [];
    private SchemaDocuments $documents;
    private CorvusErrorMapper $mapper;
    private RequestArgumentCoercer $coercer;

    public function __construct()
    {
        if (! extension_loaded('corvus_json_schema')) {
            throw new JsonSchemaEngineUnavailableException('The corvus_json_schema extension is not loaded.');
        }

        $this->documents = new SchemaDocuments();
        $this->mapper = new CorvusErrorMapper($this->documents);
        $this->coercer = new RequestArgumentCoercer($this->documents);
    }

    /** {@inheritDoc} */
    #[Override]
    public function validateRequest(array $arguments, string $schemaFile): array
    {
        return $this->validate((object) $this->coercer->coerce($arguments, $schemaFile), $schemaFile);
    }

    /** {@inheritDoc} */
    #[Override]
    public function validateResponse(array|stdClass $body, string $schemaFile): array
    {
        return $this->validate($body, $schemaFile);
    }

    /** @return JsonSchemaValidatorErrors */
    private function validate(mixed $value, string $schemaFile): array
    {
        $validator = $this->validator($schemaFile);
        if ($validator->isValid($value)) {
            return [];
        }

        $collector = new Collector(ResultsLevel::Detailed);
        $validator->evaluate($value, $collector);

        return $this->mapper->map($collector->results(), $schemaFile, $value);
    }

    private function validator(string $schemaFile): Validator
    {
        $cached = $this->validators[$schemaFile] ?? null;
        if ($cached !== null && $this->isFresh($cached[0])) {
            return $cached[1];
        }

        $stamps = [];
        $resolver = function (string $uri) use (&$stamps): stdClass|bool|null {
            $path = $this->documents->pathOf($uri);
            if ($path !== null) {
                $stamps[$path] = (string) $this->documents->stamp($path);
            }

            return $this->documents->resolve($uri);
        };
        $stamp = $this->documents->stamp($schemaFile);
        if ($stamp === null) {
            throw new JsonSchemaNotFoundException($schemaFile);
        }

        $stamps[$schemaFile] = $stamp;
        $validator = Validator::compile($this->documents->load($schemaFile), [
            'baseUri' => 'file://' . $schemaFile,
            'resolver' => $resolver,
            'defaultDialect' => Dialect::Draft7,
            'assertFormat' => true,
        ]);
        $this->validators[$schemaFile] = [$stamps, $validator];

        return $validator;
    }

    /** @param array<string, string> $stamps */
    private function isFresh(array $stamps): bool
    {
        foreach ($stamps as $path => $stamp) {
            if ($this->documents->stamp($path) !== $stamp) {
                return false;
            }
        }

        return true;
    }
}
