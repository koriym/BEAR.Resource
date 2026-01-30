<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\Exception\MethodNotAllowedException;
use BEAR\Resource\Module\HalModule;
use BEAR\Resource\Module\ResourceModule;
use FakeVendor\Sandbox\Resource\App\Blog;
use FakeVendor\Sandbox\Resource\App\Href\Hasembed;
use FakeVendor\Sandbox\Resource\App\Href\Origin;
use FakeVendor\Sandbox\Resource\App\Href\Target;
use FakeVendor\Sandbox\Resource\Page\Index;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\Di\NullModule;
use Ray\Di\ProviderInterface;

use function assert;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function unserialize;

use const E_USER_DEPRECATED;

class ResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(new FakeSchemeModule(new ResourceModule('FakeVendor\Sandbox')), __DIR__ . '/tmp');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testManualConstruction(): void
    {
        $injector = new Injector(new NullModule(), __DIR__ . '/tmp');
        $scheme = (new SchemeCollection())
            ->scheme('app')->host('self')->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox'))
            ->scheme('page')->host('self')->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox'))
            ->scheme('nop')->host('self')->toAdapter(new FakeNop());
        $invoker = (new InvokerFactory())();
        $factory = new Factory($scheme, new UriFactory());
        /** @var ProviderInterface<LinkCrawlerInterface> $linkCrawlerProvider */
        $linkCrawlerProvider = new class ($invoker, $factory) implements ProviderInterface {
            public function __construct(
                private readonly InvokerInterface $invoker,
                private readonly FactoryInterface $factory,
            ) {
            }

            public function get(): LinkCrawlerInterface
            {
                return new LinkCrawler($this->invoker, $this->factory);
            }
        };
        /** @var ProviderInterface<LinkerInterface> $linkerProvider */
        $linkerProvider = new class ($invoker, $factory, $linkCrawlerProvider) implements ProviderInterface {
            /** @param ProviderInterface<LinkCrawlerInterface> $linkCrawlerProvider */
            public function __construct(
                private readonly InvokerInterface $invoker,
                private readonly FactoryInterface $factory,
                private readonly ProviderInterface $linkCrawlerProvider,
            ) {
            }

            public function get(): LinkerInterface
            {
                return new Linker($this->invoker, $this->factory, $this->linkCrawlerProvider);
            }
        };
        $uri = new UriFactory('app://self');
        $resource = new ResourceClient($factory, $invoker, new Anchor(), $linkerProvider, $uri);
        $this->assertInstanceOf(ResourceClientInterface::class, $resource);
    }

    public function testNewInstance(): void
    {
        $instance = $this->resource->newInstance('page://self/index');
        $this->assertInstanceOf(Index::class, $instance);
    }

    public function testLazyRequest(): void
    {
        $instance = $this->resource->createRequest(Request::GET, 'page://self/index')->request();
        $this->assertInstanceOf(Request::class, $instance);
    }

    public function testEagerRequest(): void
    {
        $request = $this->resource->createRequest(Request::GET, 'page://self/index');
        assert($request instanceof Request);
        $instance = $request->eager->request();
        $this->assertInstanceOf(Index::class, $instance);
    }

    public function testWithQueryRequest(): void
    {
        $request = $this->resource->createRequest(Request::GET, 'page://self/index', ['id' => 1]);
        assert($request instanceof Request);
        $instance = $request->eager->request();
        $this->assertSame(1, $instance->body);
    }

    public function testWithAddRequestOverrideQuery(): void
    {
        $request = $this->resource->createRequest(Request::GET, 'page://self/index', ['id' => 1])->addQuery(
            ['id' => 2],
        );
        assert($request instanceof Request);
        $instance = $request->eager->request();
        $this->assertSame(2, $instance->body);
    }

    public function testObject(): void
    {
        $ro = new Index();
        $ro->uri = new Uri('page://self/index');
        $request = $this->resource->object($ro);
        assert($request instanceof Request);
        $instance = $request->eager->request();
        $this->assertInstanceOf(Index::class, $instance);
    }

    public function testHref(): void
    {
        $author = $this->resource->get('app://self/author', ['id' => 1]);
        $blog = $this->resource->href('blog', [], $author);
        $this->assertInstanceOf(Blog::class, $blog);
    }

    public function testHrefInResourceObject(): void
    {
        $origin = $this->resource->get('app://self/href/origin', ['id' => 1]);
        assert($origin instanceof ResourceObject);
        $this->assertInstanceOf(Origin::class, $origin);
        $this->assertInstanceOf(Target::class, $origin['next']);
        $next = $origin['next'];
        assert($next instanceof ResourceObject);
        $this->assertSame($next['id'], 1);
    }

    public function testHrefInResourceObjectHasEmbed(): void
    {
        $origin = $this->resource->get('app://self/href/hasembed', ['id' => 1]);
        assert($origin instanceof ResourceObject);
        $this->assertInstanceOf(Hasembed::class, $origin);
        $this->assertInstanceOf(Target::class, $origin['next']);
        $next = $origin['next'];
        assert($next instanceof ResourceObject);
        $this->assertSame($next['id'], 1);
    }

    public function testLinkSelf(): void
    {
        $request = $this->resource->createRequest(Request::GET, 'app://self/author', ['id' => 1])->linkSelf('blog')->request();
        assert($request instanceof Request);
        $this->assertSame('blog', $request->links[0]->key);
        $this->assertSame(LinkType::SELF_LINK, $request->links[0]->type);
        $ro = $request();
        $this->assertSame(200, $ro->code);
        $this->assertSame(['id' => 12, 'name' => 'Aramis blog'], $ro->body);
    }

    public function testLinkNew(): void
    {
        $request = $this->resource->createRequest(Request::GET, 'app://self/author', ['id' => 1])->linkNew('blog')->request();
        assert($request instanceof Request);
        $this->assertSame('blog', $request->links[0]->key);
        $this->assertSame(LinkType::NEW_LINK, $request->links[0]->type);
        $ro = $request();
        $this->assertSame(200, $ro->code);
        $this->assertSame(
            [
                'name' => 'Aramis',
                'age' => 16,
                'blog_id' => 12,
                'blog' => ['id' => 12, 'name' => 'Aramis blog'],
            ],
            $ro->body,
        );
    }

    /** @return array<string, array<mixed>|int|string> */
    public function testLinkCrawl(): array
    {
        $request = $this->resource->createRequest(Request::GET, 'app://self/blog', ['id' => 11])->linkCrawl('tree')->request();
        assert($request instanceof Request);
        $this->assertSame('tree', $request->links[0]->key);
        $this->assertSame(LinkType::CRAWL_LINK, $request->links[0]->type);
        $ro = $request();
        $this->assertSame(200, $ro->code);
        $expected = [
            'id' => 11,
            'name' => 'Athos blog',
            'post' => [
                'id' => '1',
                'author_id' => '1',
                'body' => 'Anna post #1',
                'meta' => [
                    0 => [
                        'id' => '1',
                        'post_id' => '1',
                        'data' => 'meta 1',
                    ],
                ],
                'tag' => [
                    0 => [
                        'id' => '1',
                        'post_id' => '1',
                        'tag_id' => '1',
                        'tag_name' => [
                            0 => [
                                'id' => '1',
                                'name' => 'zim',
                            ],
                        ],
                        'tag_type' => [0 => 'type1'],
                    ],
                    1 => [
                        'id' => '2',
                        'post_id' => '1',
                        'tag_id' => '2',
                        'tag_name' => [
                            0 => [
                                'id' => '2',
                                'name' => 'dib',
                            ],
                        ],
                        'tag_type' => [0 => 'type1'],
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $ro->body);

        return $expected;
    }

    public function testCrawl(): void
    {
        $ro = $this->resource->crawl('app://self/blog', 'tree', ['id' => 11]);
        $this->assertSame(200, $ro->code);
        $expected = [
            'id' => 11,
            'name' => 'Athos blog',
            'post' => [
                'id' => '1',
                'author_id' => '1',
                'body' => 'Anna post #1',
                'meta' => [
                    0 => [
                        'id' => '1',
                        'post_id' => '1',
                        'data' => 'meta 1',
                    ],
                ],
                'tag' => [
                    0 => [
                        'id' => '1',
                        'post_id' => '1',
                        'tag_id' => '1',
                        'tag_name' => [
                            0 => [
                                'id' => '1',
                                'name' => 'zim',
                            ],
                        ],
                        'tag_type' => [0 => 'type1'],
                    ],
                    1 => [
                        'id' => '2',
                        'post_id' => '1',
                        'tag_id' => '2',
                        'tag_name' => [
                            0 => [
                                'id' => '2',
                                'name' => 'dib',
                            ],
                        ],
                        'tag_type' => [0 => 'type1'],
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $ro->body);
    }

    public function testHal(): void
    {
        $resource = (new Injector(new HalModule(new ResourceModule('FakeVendor\Sandbox')), __DIR__ . '/tmp'))->getInstance(
            ResourceInterface::class,
        );
        $user = $resource->get('app://self/author', ['id' => 1]);
        $expected = '{
    "name": "Aramis",
    "age": 16,
    "blog_id": 12,
    "_links": {
        "self": {
            "href": "/author?id=1"
        },
        "blog": {
            "href": "app://self/blog?id=12"
        }
    }
}
';
        $this->assertJsonStringEqualsJsonString($expected, (string) $user);
    }

    public function testConstructorHasAnotherResourceRequest(): void
    {
        $body = $this->resource->post('app://self/holder')->body;
        $this->assertTrue($body);
    }

    public function testAssistedParameter(): ResourceInterface
    {
        $injector = new Injector(new FakeAssistedModule(new FakeSchemeModule(new ResourceModule('FakeVendor\Sandbox'))), __DIR__ . '/tmp');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $ro = $this->resource->get('page://self/assist');
        /** @var ResourceObject $ro */
        $this->assertSame('login_id:assisted01', $ro->body);

        return $this->resource;
    }

    #[Depends('testAssistedParameter')]
    public function testPreventAssistedParameterOverride(ResourceInterface $resource): void
    {
        $ro = $resource->get('page://self/assist', ['login_id' => '_WILL_BE_IGNORED_']);
        /** @var ResourceObject $ro */
        $this->assertSame('login_id:assisted01', $ro->body);
    }

    public function testGet(): void
    {
        $ro = $this->resource->get('page://self/index', ['id' => 1]);
        $this->assertSame(1, $ro->body);
    }

    public function testPost(): void
    {
        $ro = $this->resource->post('page://self/index', ['name' => 'bear']);
        $this->assertSame('post bear', $ro->body);
    }

    public function testPut(): void
    {
        $ro = $this->resource->put('page://self/index', ['name' => 'bear']);
        $this->assertSame('put bear', $ro->body);
    }

    public function testPatch(): void
    {
        $ro = $this->resource->patch('page://self/index', ['name' => 'bear']);
        $this->assertSame('patch bear', $ro->body);
    }

    public function testDelete(): void
    {
        $ro = $this->resource->delete('page://self/index', ['name' => 'bear']);
        $this->assertSame('delete bear', $ro->body);
    }

    public function testHead(): void
    {
        $ro = $this->resource->head('page://self/index', ['name' => 'bear']);
        $this->assertSame('1', $ro->headers['X-BEAR']);
        $this->assertNull($ro->body);
    }

    public function testHeadNotAllowed(): void
    {
        $this->expectException(MethodNotAllowedException::class);
        $this->resource->head('page://self/hello-world');
    }

    public function testMultipleRequest(): void
    {
        $view = (string) $this->resource->get('/fake-loop');
        $expected = '{
    "1": {
        "num": "1"
    },
    "2": {
        "num": "2"
    },
    "3": {
        "num": "3"
    },
    "4": {
        "num": "4"
    },
    "5": {
        "num": "5"
    }
}
';
        $this->assertJsonStringEqualsJsonString($expected, $view);
    }

    public function testOptions(): void
    {
        $ro = $this->resource->options('page://self/index');
        $this->assertInstanceOf(ResourceObject::class, $ro);
        $this->assertSame('GET, POST, PUT, PATCH, DELETE', $ro->headers['Allow']);
    }

    public function testSerialize(): void
    {
        $this->assertInstanceOf(ResourceInterface::class, unserialize(serialize($this->resource)));
    }

    public function testInvokeWrongType(): void
    {
        $this->expectException(BadRequestException::class);
        $this->resource->createRequest(Request::GET, 'app://self/stone')(['id' => '']);
    }

    public function testCreateRequest(): void
    {
        $request = $this->resource->createRequest(Request::POST, 'app://self/blog', ['name' => 'test']);
        $this->assertInstanceOf(RequestInterface::class, $request);
        assert($request instanceof Request);
        $this->assertSame('post', $request->method);
    }

    public function testLegacyFluentInterfaceFallback(): void
    {
        // Resource.__get() supports legacy fluent interface with deprecation warning
        set_error_handler(static function (int $errno, string $errstr): bool {
            return true; // Suppress the deprecation warning
        }, E_USER_DEPRECATED);

        try {
            /** @var Resource $resource */
            $resource = $this->resource;
            $legacyResource = $resource->get;

            // Returns the same Resource instance
            $this->assertInstanceOf(Resource::class, $legacyResource);

            // The legacy Resource can use the fluent interface
            /** @psalm-suppress DeprecatedMethod */
            $request = $legacyResource->uri('page://self/index');
            $this->assertInstanceOf(RequestInterface::class, $request);
        } finally {
            restore_error_handler();
        }
    }

    public function testLegacyResourceManualConstruction(): void
    {
        $injector = new Injector(new NullModule(), __DIR__ . '/tmp');
        $scheme = (new SchemeCollection())
            ->scheme('app')->host('self')->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox'))
            ->scheme('page')->host('self')->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox'))
            ->scheme('nop')->host('self')->toAdapter(new FakeNop());
        $invoker = (new InvokerFactory())();
        $factory = new Factory($scheme, new UriFactory());
        /** @var ProviderInterface<LinkCrawlerInterface> $linkCrawlerProvider */
        $linkCrawlerProvider = new class ($invoker, $factory) implements ProviderInterface {
            public function __construct(
                private readonly InvokerInterface $invoker,
                private readonly FactoryInterface $factory,
            ) {
            }

            public function get(): LinkCrawlerInterface
            {
                return new LinkCrawler($this->invoker, $this->factory);
            }
        };
        $linker = new Linker($invoker, $factory, $linkCrawlerProvider);
        $uri = new UriFactory('app://self');

        $linkerProvider = new /** @template-implements ProviderInterface<LinkerInterface> */ class ($linker) implements ProviderInterface {
            public function __construct(private readonly LinkerInterface $linker)
            {
            }

            public function get(): LinkerInterface
            {
                return $this->linker;
            }
        };
        $resource = new Resource($factory, $invoker, new Anchor(), $linkerProvider, $uri);
        $this->assertInstanceOf(ResourceInterface::class, $resource);

        // Test that fluent interface works
        set_error_handler(static function (int $errno, string $errstr): bool {
            return true; // Suppress the deprecation warning
        }, E_USER_DEPRECATED);

        try {
            /** @psalm-suppress DeprecatedMethod */
            $request = $resource->get->uri('page://self/index');
            $this->assertInstanceOf(RequestInterface::class, $request);
        } finally {
            restore_error_handler();
        }
    }
}
