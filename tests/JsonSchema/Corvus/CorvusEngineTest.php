<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\Exception\JsonSchemaNotFoundException;
use BEAR\Resource\Exception\UnsupportedSchemaException;
use BEAR\Resource\JsonSchema\JsonSchemaErrorMapper;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_column;
use function dirname;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function glob;
use function is_dir;
use function json_decode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function uniqid;
use function unlink;

/** @psalm-type Row = array{property: string, pointer: string, message: string, constraint: array{name: string, params: array<string, mixed>}} */
class CorvusEngineTest extends TestCase
{
    private CorvusEngine $engine;
    private string $order;
    private string $request;

    /** @var list<string> */
    private array $dirs = [];

    protected function setUp(): void
    {
        if (! extension_loaded('corvus_json_schema')) {
            $this->markTestSkipped('corvus_json_schema extension is not loaded.');
        }

        $this->engine = new CorvusEngine();
        $dir = dirname(__DIR__, 2) . '/Fake/corvus_schema';
        $this->order = $dir . '/order.json';
        $this->request = $dir . '/request.json';
    }

    public function testValidResponse(): void
    {
        $body = $this->body('{"id":1,"price":{"amount":10,"currency":"JPY"},"tags":["a"],"contact":"a@example.com","meta":{}}');

        $this->assertSame([], $this->response($body, $this->order));
    }

    public function testRefAcrossFiles(): void
    {
        $errors = $this->response($this->body('{"id":1,"price":{"amount":0,"currency":"jpy"}}'), $this->order);

        $this->assertSame(['/price/amount', '/price/currency'], array_column($errors, 'pointer'));
        $this->assertSame(['minimum', 'pattern'], $this->names($errors));
        $this->assertSame(['minimum' => 1], $errors[0]['constraint']['params']);
        $this->assertSame(['pattern' => '^[A-Z]{3}$'], $errors[1]['constraint']['params']);
        $this->assertSame('price.amount', $errors[0]['property']);
    }

    public function testRequiredIsReportedAtTheMissingProperty(): void
    {
        $errors = $this->response($this->body('{"id":1}'), $this->order);

        $this->assertSame('/price', $errors[0]['pointer']);
        $this->assertSame('required', $errors[0]['constraint']['name']);
        $this->assertSame(['property' => 'price'], $errors[0]['constraint']['params']);
    }

    public function testAdditionalPropertyIsReportedAtTheParent(): void
    {
        $errors = $this->response($this->body('{"id":1,"price":{"amount":1,"currency":"JPY"},"zz":1}'), $this->order);

        $this->assertSame('', $errors[0]['pointer']);
        $this->assertSame('additionalProp', $errors[0]['constraint']['name']);
        $this->assertSame(['property' => 'zz'], $errors[0]['constraint']['params']);
    }

    public function testHttpsDraft04SchemaIdSelectsDraft04(): void
    {
        // Draft-04 boolean exclusiveMinimum: 1 is excluded. Under the 2020-12 fallback this keyword would not behave so.
        $errors = $this->response($this->body('{"id":1,"price":{"amount":1,"currency":"JPY"},"rank":1}'), $this->order);

        $this->assertSame(['exclusiveMinimum'], $this->names($errors));
        $this->assertSame(['minimum' => 1], $errors[0]['constraint']['params']);
    }

    public function testMinItemsParamsCarryFound(): void
    {
        $errors = $this->response($this->body('{"id":1,"price":{"amount":1,"currency":"JPY"},"tags":[]}'), $this->order);

        $this->assertSame(['minItems' => 1, 'found' => 0], $errors[0]['constraint']['params']);
    }

    public function testAnyOfIsOneError(): void
    {
        $errors = $this->response($this->body('{"id":1,"price":{"amount":1,"currency":"JPY"},"contact":null}'), $this->order);

        $this->assertSame(['anyOf'], $this->names($errors));
        $this->assertSame('/contact', $errors[0]['pointer']);
    }

    public function testTypeParamsMatchJustinrainbowWording(): void
    {
        $errors = $this->response($this->body('{"id":"x","price":{"amount":1,"currency":"JPY"}}'), $this->order);

        $this->assertSame(['found' => 'string', 'expected' => 'an integer'], $errors[0]['constraint']['params']);
        $this->assertSame('String value found, but an integer is required', $errors[0]['message']);
    }

    public function testEmptyObjectAndEmptyArrayAreDistinct(): void
    {
        $this->assertSame([], $this->response($this->body('{"id":1,"price":{"amount":1,"currency":"JPY"},"meta":{}}'), $this->order));

        $errors = $this->response($this->body('{"id":1,"price":{"amount":1,"currency":"JPY"},"meta":[]}'), $this->order);
        $this->assertSame(['type'], $this->names($errors));
        $this->assertSame('/meta', $errors[0]['pointer']);
    }

    public function testRequestWithoutArgumentsIsAnObject(): void
    {
        $errors = $this->request([], $this->request);

        $this->assertSame(['required'], $this->names($errors));
    }

    public function testRequestStringsAreCoercedToDeclaredScalarTypes(): void
    {
        $arguments = ['id' => '12', 'price' => '1.5', 'active' => 'true', 'code' => '007', 'either' => '3'];

        $this->assertSame([], $this->request($arguments, $this->request));
    }

    public function testRequestCoercionLeavesUncastableAndStringTypedValues(): void
    {
        $errors = $this->request(['id' => 'abc', 'active' => 'yes', 'code' => 5], $this->request);

        $this->assertSame(['/id', '/active', '/code'], array_column($errors, 'pointer'));
        $this->assertSame(['type', 'type', 'type'], $this->names($errors));
    }

    public function testRequestCoercionDoesNotMaskSchemaConstraints(): void
    {
        $errors = $this->request(['id' => '0'], $this->request);

        $this->assertSame(['minimum'], $this->names($errors));
    }

    public function testErrorMessageTemplatesRenderEngineParams(): void
    {
        $errors = $this->response($this->body('{"id":0,"price":{"amount":1,"currency":"JPY"},"note":"too long"}'), $this->order);
        $schema = json_decode((string) file_get_contents($this->order));
        $this->assertInstanceOf(stdClass::class, $schema);

        $mapper = new JsonSchemaErrorMapper();
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $mapper->toJsonSchemaError($error, $schema)->message;
        }

        $this->assertSame(['Id must be at least 1.', 'Note must be at most 5 characters.'], $messages);
    }

    public function testValidatorIsRecompiledWhenTheSchemaFileChanges(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'corvus-schema-');
        $this->assertNotFalse($file);

        try {
            file_put_contents($file, '{"type":"object","required":["a"]}');
            $this->assertCount(1, $this->response(new stdClass(), $file));

            file_put_contents($file, '{"type":"object"}');
            touch($file, 2_000_000_000);
            $this->assertSame([], $this->response(new stdClass(), $file));
        } finally {
            unlink($file);
        }
    }

    public function testHttpsDraft04IsHonouredInReferencedDocuments(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"properties":{"a":{"$ref":"ref.json"}}}', 'ref.json' => '{"$schema":"https://json-schema.org/draft-04/schema#","minimum":1,"exclusiveMinimum":true}']);

        $this->assertSame(['exclusiveMinimum'], $this->names($this->response($this->body('{"a":1}'), $dir . '/root.json')));
        $this->assertSame([], $this->response($this->body('{"a":2}'), $dir . '/root.json'));
    }

    public function testRootDialectDoesNotLeakIntoReferencedDocuments(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"$schema":"https://json-schema.org/draft-04/schema#","properties":{"a":{"$ref":"ref.json"}}}', 'ref.json' => '{"exclusiveMinimum":1}']);

        $this->assertSame(['exclusiveMinimum'], $this->names($this->response($this->body('{"a":1}'), $dir . '/root.json')));
    }

    public function testValidatorIsRecompiledWhenAReferencedFileChanges(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"properties":{"a":{"$ref":"ref.json"}}}', 'ref.json' => '{"type":"integer"}']);
        $this->assertCount(1, $this->response($this->body('{"a":"x"}'), $dir . '/root.json'));

        $mtime = (int) filemtime($dir . '/ref.json');
        file_put_contents($dir . '/ref.json', '{"type":"string"}');
        touch($dir . '/ref.json', $mtime);

        $this->assertSame([], $this->response($this->body('{"a":"x"}'), $dir . '/root.json'));
    }

    public function testErrorParamsSurviveARootRef(): void
    {
        $errors = $this->response($this->body('{"id":"x"}'), dirname(__DIR__, 2) . '/Fake/corvus_schema/root_ref.json');

        $this->assertSame('String value found, but an integer is required', $errors[0]['message']);
        $this->assertSame(['found' => 'string', 'expected' => 'an integer'], $errors[0]['constraint']['params']);
    }

    public function testRequestCoercionResolvesRefsAgainstTheRootRefTarget(): void
    {
        $file = dirname(__DIR__, 2) . '/Fake/corvus_schema/root_ref.json';

        $this->assertSame([], $this->request(['id' => '5'], $file));
    }

    public function testBooleanExclusiveBoundWithoutSchemaIsRejected(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"properties":{"a":{"minimum":1,"exclusiveMinimum":true}}}']);

        $this->expectException(UnsupportedSchemaException::class);
        $this->expectExceptionMessage('draft-04');
        $this->response($this->body('{"a":1}'), $dir . '/root.json');
    }

    public function testBooleanExclusiveBoundWithoutSchemaIsRejectedInReferencedDocuments(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"properties":{"a":{"$ref":"ref.json"}}}', 'ref.json' => '{"maximum":1,"exclusiveMaximum":true}']);

        $this->expectException(UnsupportedSchemaException::class);
        $this->response($this->body('{"a":1}'), $dir . '/root.json');
    }

    public function testErrorParamsSurviveARootRefWithSiblings(): void
    {
        $dir = $this->schemaDir([
            'root.json' => '{"$schema":"https://json-schema.org/draft/2020-12/schema","$ref":"sub.json","properties":{"name":{"type":"string"}}}',
            'sub.json' => '{"properties":{"id":{"type":"integer"}}}',
        ]);
        $errors = $this->response($this->body('{"id":"x","name":5}'), $dir . '/root.json');

        $this->assertSame(['/name', '/id'], array_column($errors, 'pointer'));
        $this->assertSame(['found' => 'integer', 'expected' => 'a string'], $errors[0]['constraint']['params']);
        $this->assertSame(['found' => 'string', 'expected' => 'an integer'], $errors[1]['constraint']['params']);
    }

    public function testPropertyNamedExclusiveMinimumIsNotADraft04Bound(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"properties":{"exclusiveMinimum":true},"$defs":{"exclusiveMaximum":false}}']);

        $this->assertSame([], $this->response($this->body('{"exclusiveMinimum":1}'), $dir . '/root.json'));
    }

    public function testBooleanExclusiveBoundUnderAPropertyNamedDefaultIsRejected(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"properties":{"default":{"minimum":1,"exclusiveMinimum":true}}}']);

        $this->expectException(UnsupportedSchemaException::class);
        $this->response($this->body('{"default":1}'), $dir . '/root.json');
    }

    public function testMissingRootSchemaFileThrows(): void
    {
        $this->expectException(JsonSchemaNotFoundException::class);
        $this->response(new stdClass(), sys_get_temp_dir() . '/corvus-missing-' . uniqid() . '.json');
    }

    public function testChainedRootRefsAreFollowed(): void
    {
        $dir = $this->schemaDir([
            'a.json' => '{"$ref":"b.json"}',
            'b.json' => '{"$ref":"c.json"}',
            'c.json' => '{"type":"object","properties":{"id":{"type":"integer","minimum":10}}}',
        ]);

        $errors = $this->response($this->body('{"id":5}'), $dir . '/a.json');
        $this->assertSame(['minimum' => 10], $errors[0]['constraint']['params']);
        $this->assertSame([], $this->request(['id' => '15'], $dir . '/a.json'));
    }

    public function testUnevaluatedPropertiesIsReportedPerProperty(): void
    {
        $dir = $this->schemaDir(['root.json' => '{"$schema":"https://json-schema.org/draft/2020-12/schema","properties":{"a":{"type":"integer"}},"unevaluatedProperties":false}']);
        $errors = $this->response($this->body('{"a":1,"zz":2}'), $dir . '/root.json');

        $this->assertCount(1, $errors);
        $this->assertSame(['property' => 'zz'], $errors[0]['constraint']['params']);
        $this->assertSame('The property zz is not evaluated by any schema and unevaluated properties are not allowed', $errors[0]['message']);
    }

    public function testObjectFoundIsReportedAsObject(): void
    {
        $errors = $this->response($this->body('{"id":{},"price":{"amount":1,"currency":"JPY"}}'), $this->order);

        $this->assertSame(['found' => 'object', 'expected' => 'an integer'], $errors[0]['constraint']['params']);
    }

    /**
     * @param array<string, string> $files
     *
     * @return string directory holding the files, removed on tear down
     */
    private function schemaDir(array $files): string
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('corvus-schemas-', true);
        mkdir($dir);
        $this->dirs[] = $dir;
        foreach ($files as $name => $json) {
            file_put_contents($dir . '/' . $name, $json);
        }

        return $dir;
    }

    protected function tearDown(): void
    {
        foreach ($this->dirs as $dir) {
            foreach (glob($dir . '/*') ?: [] as $file) {
                unlink($file);
            }

            if (! is_dir($dir)) {
                continue;
            }

            rmdir($dir);
        }
    }

    public function testBooleanFalseSchemaStillReportsAnError(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'corvus-schema-');
        $this->assertNotFalse($file);

        try {
            file_put_contents($file, 'false');
            $errors = $this->response(new stdClass(), $file);
        } finally {
            unlink($file);
        }

        $this->assertCount(1, $errors);
    }

    /**
     * @param array<mixed>|stdClass $body
     *
     * @return list<Row>
     */
    private function response(array|stdClass $body, string $file): array
    {
        /** @var list<Row> $rows */
        $rows = $this->engine->validateResponse($body, $file);

        return $rows;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return list<Row>
     */
    private function request(array $arguments, string $file): array
    {
        /** @var list<Row> $rows */
        $rows = $this->engine->validateRequest($arguments, $file);

        return $rows;
    }

    /**
     * @param list<Row> $errors
     *
     * @return list<string>
     */
    private function names(array $errors): array
    {
        return array_column(array_column($errors, 'constraint'), 'name');
    }

    private function body(string $json): stdClass
    {
        $body = json_decode($json);
        $this->assertInstanceOf(stdClass::class, $body);

        return $body;
    }
}
