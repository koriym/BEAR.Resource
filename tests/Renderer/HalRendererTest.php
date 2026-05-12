<?php

declare(strict_types=1);

namespace BEAR\Resource\Renderer;

use BEAR\Resource\FakeChild;
use BEAR\Resource\FakeHal;
use BEAR\Resource\FakeLazyRequest;
use BEAR\Resource\HalLinker;
use BEAR\Resource\HalRenderer;
use BEAR\Resource\InvokerFactory;
use BEAR\Resource\NullReverseLinker;
use BEAR\Resource\Request;
use BEAR\Resource\Uri;
use PHPUnit\Framework\TestCase;

class HalRendererTest extends TestCase
{
    private FakeHal $ro;

    protected function setUp(): void
    {
        $this->ro = new FakeHal();
        $this->ro->uri = new Uri('app://self/dummy');
        $this->ro->setRenderer(new HalRenderer(new HalLinker(new NullReverseLinker())));
    }

    public function testRender(): void
    {
        $ro = $this->ro->onGet();
        $data = (string) $ro;
        $expected = <<<'EOT'
{
    "one": 1,
    "_embedded": {
        "two": {
            "tree": 3,
            "_links": {
                "self": {
                    "href": "/bear/resource/fakechild"
                }
            }
        }
    },
    "_links": {
        "self": {
            "href": "/dummy"
        },
        "profile": {
            "href": "/profile"
        }
    }
}

EOT;
        $this->assertJsonStringEqualsJsonString($expected, $data);
    }

    public function testRenderScalar(): void
    {
        $this->ro->body = 1;
        $data = (string) $this->ro;
        $expected = <<<'EOT'
{
    "value": 1,
    "_links": {
        "self": {
            "href": "/dummy"
        },
        "profile": {
            "href": "/profile"
        }
    }
}

EOT;
        $this->assertJsonStringEqualsJsonString($expected, $data);
    }

    public function testHeader(): void
    {
        $ro = $this->ro->onGet();
        (string) $ro; // @phpstan-ignore-line
        $expected = 'application/hal+json';
        $this->assertSame($expected, $ro->headers['Content-Type']);
    }

    public function testBodyLink(): void
    {
        $ro = $this->ro->onGet(true);
        $actual = (string) $ro;
        $expected = <<<'EOT'
{
    "one": 1,
    "_links": {
        "self": {
            "href": "/dummy"
        },
        "profile": {
            "href": "/changed-profile"
        }
    },
    "_embedded": {
        "two": {
            "tree": 3,
            "_links": {
                "self": {
                    "href": "/bear/resource/fakechild"
                }
            }
        }
    }
}

EOT;
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testLocationHeader(): void
    {
        $ro = $this->ro->onGet();
        $ro->headers['Location'] = '/foo';
        (string) $ro; // @phpstan-ignore-line
        $this->assertSame('/foo', $ro->headers['Location']);
    }

    public function testNonArrayBody(): void
    {
        $ro = $this->ro->onGet();
        $ro->body = '1';
        $actual = (string) $ro;
        $expected = <<<'EOT'
{
    "value": "1",
    "_links": {
        "self": {
            "href": "/dummy"
        },
        "profile": {
            "href": "/profile"
        }
    }
}

EOT;
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testRenderNullBody(): void
    {
        $this->ro->body = null;
        $actual = (string) $this->ro;
        $expected = <<<'EOT'
{
    "_links": {
        "self": {
            "href": "/dummy"
        },
        "profile": {
            "href": "/profile"
        }
    }
}

EOT;
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testBodyObject(): void
    {
        $this->ro->body = new class {
            public int $a = 1;
        };
        $actual = (string) $this->ro;

        $this->assertStringContainsString('"a": 1,', $actual);
    }

    /**
     * Regression: HalRenderer must evaluate embedded AbstractRequest instances
     * via __toString() so that lazy/batch decorators (e.g. bear/async's
     * AsyncRequest) get a chance to short-circuit invoke(). FakeLazyRequest
     * throws from its underlying invoker if invoke() is ever reached, so if
     * the renderer bypassed __toString() this test would fail.
     */
    public function testEmbedEvaluationGoesThroughToString(): void
    {
        $child = new FakeChild();
        $child->uri = new Uri('app://self/bear/resource/fakechild');
        $lazy = new FakeLazyRequest('{"name": "stubbed"}', $child);

        $this->ro->body = ['stub' => $lazy];

        $halRenderer = new HalRenderer(new HalLinker(new NullReverseLinker()));
        $halRenderer->renderHal($this->ro);

        $this->assertIsArray($this->ro->body);
        $this->assertArrayHasKey('_embedded', $this->ro->body);
        $this->assertSame(
            ['name' => 'stubbed'],
            (array) $this->ro->body['_embedded']['stub'],
        );
    }

    public function testDifferentSchemaEmbedSkipsRendererPrePass(): void
    {
        $child = new FakeChild();
        $child->uri = new Uri('page://self/bear/resource/fakechild');

        $this->ro->body = [
            'different' => new Request((new InvokerFactory())(), $child),
        ];

        $halRenderer = new HalRenderer(new HalLinker(new NullReverseLinker()));
        $halRenderer->renderHal($this->ro);

        $this->assertIsArray($this->ro->body);
        $this->assertArrayHasKey('_embedded', $this->ro->body);
        $this->assertSame(
            ['tree' => 3],
            $this->ro->body['_embedded']['different'],
        );
    }
}
