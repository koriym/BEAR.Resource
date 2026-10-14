<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Corvus;

use BEAR\Resource\JsonSchema\JustinrainbowEngine;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_slice;
use function explode;
use function extension_loaded;
use function file_put_contents;
use function implode;
use function is_array;
use function json_decode;
use function json_encode;
use function sort;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Feeds the same schema and body to both engines and compares the error rows
 * (property, pointer, constraint name, params, message).
 *
 * Cases in `differences()` are known divergences; each states why.
 */
class EngineParityTest extends TestCase
{
    protected function setUp(): void
    {
        if (extension_loaded('corvus_json_schema')) {
            return;
        }

        $this->markTestSkipped('corvus_json_schema extension is not loaded.');
    }

    /** @return Generator<string, array{string, string}> */
    public static function identical(): Generator
    {
        $big = '{"type":"object","required":["a"],"additionalProperties":false,"properties":{'
            . '"n":{"type":"object","required":["x"],"properties":{"x":{"type":"integer","minimum":3,"maximum":9,"multipleOf":2}}},'
            . '"s":{"type":"string","minLength":2,"maxLength":3,"pattern":"^a"},"e":{"enum":["a","b"]},"t":{"type":["string","null"]},'
            . '"arr":{"type":"array","minItems":2,"maxItems":3,"uniqueItems":true,"items":{"type":"integer"}},'
            . '"em":{"type":"string","format":"email"},"dt":{"type":"string","format":"date-time"},"u":{"type":"string","format":"uri"},'
            . '"ip":{"type":"string","format":"ipv4"},"c":{"const":5}}}';

        yield 'every common keyword' => [$big, '{"n":{"x":1},"s":"zzzz","e":"c","t":5,"arr":[1,1,"q"],"em":"x","dt":"x","u":"x","ip":"x","c":6,"zz":1}'];
        yield 'draft-04 boolean exclusive bounds' => ['{"$schema":"https://json-schema.org/draft-04/schema#","properties":{"a":{"minimum":1,"exclusiveMinimum":true,"maximum":5,"exclusiveMaximum":true}}}', '{"a":5}'];
        yield 'oneOf' => ['{"properties":{"a":{"oneOf":[{"type":"integer"},{"minimum":1}]}}}', '{"a":5}'];
        yield 'not' => ['{"properties":{"a":{"not":{"type":"integer"}}}}', '{"a":5}'];
        yield 'items with nested required' => ['{"properties":{"a":{"type":"array","items":{"type":"object","required":["k"],"properties":{"k":{"type":"string"}}}}}}', '{"a":[{"k":"z"},{},{"k":1}]}'];
        yield 'tuple items without additionalItems' => ['{"$schema":"http://json-schema.org/draft-07/schema#","properties":{"a":{"type":"array","items":[{"type":"integer"}],"additionalItems":false}}}', '{"a":[1,2]}'];
        yield 'additionalProperties schema' => ['{"properties":{"a":{"type":"object","additionalProperties":{"type":"integer"}}}}', '{"a":{"x":"s","y":1}}'];
        yield 'minProperties' => ['{"minProperties":2,"maxProperties":3}', '{"a":1}'];
        yield 'dependencies' => ['{"dependencies":{"a":["b"]}}', '{"a":1}'];
        yield 'root type' => ['{"type":"object"}', '[1]'];
        yield 'nested required' => ['{"properties":{"o":{"properties":{"p":{"required":["q"]}}}}}', '{"o":{"p":{}}}'];
        yield 'date and time formats' => ['{"properties":{"d":{"format":"date"},"t":{"format":"time"},"h":{"format":"hostname"}}}', '{"d":"2020-13-01","t":"zz","h":"-x-"}'];
        yield 'enum, boolean, null types' => ['{"properties":{"a":{"enum":[1,2,3]},"b":{"type":"boolean"},"c":{"type":"null"}}}', '{"a":4,"b":"x","c":0}'];
        yield '$ref to definitions' => ['{"definitions":{"pos":{"type":"integer","minimum":1}},"properties":{"a":{"$ref":"#/definitions/pos"},"b":{"type":"array","items":{"$ref":"#/definitions/pos"}}}}', '{"a":0,"b":[1,0]}'];
        yield 'multibyte length' => ['{"properties":{"s":{"type":"string","minLength":2}}}', '{"s":"あ"}'];
        yield 'escaped property names' => ['{"properties":{"a/b":{"type":"integer"}},"required":["c/d"]}', '{"a/b":"x"}'];
        yield 'empty object missing required' => ['{"type":"object","required":["x"]}', '{}'];
    }

    /** @return Generator<string, array{string, string, list<string>}> */
    public static function differences(): Generator
    {
        // Spec-correct difference: justinrainbow reports missingMinimum/missingMaximum for numeric exclusive bounds (draft-06+).
        yield 'numeric exclusiveMinimum' => [
            '{"$schema":"http://json-schema.org/draft-07/schema#","properties":{"a":{"exclusiveMinimum":1}}}',
            '{"a":1}',
            ['a|/a|exclusiveMinimum|{"minimum":1}'],
        ];

        // Structural difference: Corvus reports the combinator once; justinrainbow adds the failures of every branch.
        yield 'anyOf branches' => [
            '{"properties":{"a":{"anyOf":[{"type":"string"},{"type":"integer"}]}}}',
            '{"a":null}',
            ['a|/a|anyOf|[]'],
        ];

        // Structural difference: justinrainbow adds an allOf row on top of the failing branch.
        yield 'allOf' => [
            '{"properties":{"a":{"allOf":[{"type":"integer"},{"minimum":10}]}}}',
            '{"a":5}',
            ['a|/a|minimum|{"minimum":10}'],
        ];

        // Spec-correct difference: [] is a JSON array, not an object (justinrainbow reads PHP arrays as objects).
        yield 'empty array for object' => [
            '{"properties":{"o":{"type":"object"}}}',
            '{"o":[]}',
            ['o|/o|type|{"found":"array","expected":"an object"}'],
        ];

        // Spec-correct difference: an object is reported as 'object'; justinrainbow calls it 'array'.
        yield 'object for string' => [
            '{"properties":{"o":{"type":"string"}}}',
            '{"o":{}}',
            ['o|/o|type|{"found":"object","expected":"a string"}'],
        ];

        // Spec-correct difference: 1.0 is an integer (draft-06+); justinrainbow reports it as a double.
        yield 'integral float for integer' => [
            '{"properties":{"n":{"type":"integer"}}}',
            '{"n":1.0}',
            [],
        ];

        // Spec-correct difference: boolean `false` property schema rejects any value.
        yield 'false schema' => [
            '{"properties":{"a":false}}',
            '{"a":1}',
            ['a|/a|false|[]'],
        ];
    }

    #[DataProvider('identical')]
    public function testEnginesAgree(string $schema, string $body): void
    {
        [$justinrainbow, $corvus] = $this->runBoth($schema, $body);

        $this->assertNotSame([], $justinrainbow);
        $this->assertSame($justinrainbow, $corvus);
    }

    /** @param list<string> $expected */
    #[DataProvider('differences')]
    public function testKnownDifferences(string $schema, string $body, array $expected): void
    {
        [$justinrainbow, $corvus] = $this->runBoth($schema, $body);

        $this->assertNotSame($justinrainbow, $corvus);
        $this->assertSame($expected, $this->brief($corvus));
    }

    /** @return array{list<string>, list<string>} */
    private function runBoth(string $schema, string $body): array
    {
        $file = tempnam(sys_get_temp_dir(), 'parity-');
        $this->assertNotFalse($file);

        try {
            file_put_contents($file, $schema);
            $data = json_decode($body);
            if (! is_array($data) && ! $data instanceof stdClass) {
                $this->fail('body must be an object or an array');
            }

            return [
                $this->normalize((new JustinrainbowEngine())->validateResponse($data, $file)),
                $this->normalize((new CorvusEngine())->validateResponse($data, $file)),
            ];
        } finally {
            unlink($file);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<string>
     */
    private function normalize(array $rows): array
    {
        $lines = [];
        foreach ($rows as $row) {
            /** @var array{name: string, params: array<string, mixed>} $constraint */
            $constraint = $row['constraint'];
            $lines[] = sprintf(
                '%s|%s|%s|%s|%s',
                (string) $row['property'],
                (string) $row['pointer'],
                $constraint['name'],
                json_encode($constraint['params']),
                (string) $row['message'],
            );
        }

        sort($lines);

        return $lines;
    }

    /**
     * @param list<string> $lines
     *
     * @return list<string>
     */
    private function brief(array $lines): array
    {
        $brief = [];
        foreach ($lines as $line) {
            $brief[] = implode('|', array_slice(explode('|', $line), 0, 4));
        }

        return $brief;
    }
}
