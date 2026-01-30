<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;

use function array_merge;
use function assert;
use function is_string;

/**
 * Resource client with mutable state - supports legacy fluent interface
 *
 * This class maintains internal state for the fluent interface pattern.
 * It is NOT coroutine-safe due to mutable $method and $request properties.
 * For coroutine-safe usage, use ResourceClient instead.
 *
 * @property $this $get
 * @property $this $post
 * @property $this $put
 * @property $this $patch
 * @property $this $delete
 * @property $this $head
 * @property $this $options
 * @psalm-import-type Query from Types
 * @codeCoverageIgnore Deprecated legacy class - use ResourceClient instead
 */
final class Resource implements ResourceInterface
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private Request $request;
    private string $method = 'get';

    /**
     * @param FactoryInterface                   $factory        Resource factory
     * @param InvokerInterface                   $invoker        Resource request invoker
     * @param AnchorInterface                    $anchor         Resource anchor
     * @param ProviderInterface<LinkerInterface> $linkerProvider Resource linker provider
     * @param UriFactory                         $uri            URI factory
     */
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly InvokerInterface $invoker,
        private readonly AnchorInterface $anchor,
        #[Set(LinkerInterface::class)]
        private readonly ProviderInterface $linkerProvider,
        private readonly UriFactory $uri,
    ) {
    }

    /**
     * Set HTTP method for fluent interface
     *
     * @deprecated Use createRequest() instead
     */
    public function __get(string $name): self
    {
        $this->method = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function newInstance($uri): ResourceObject
    {
        if (is_string($uri)) {
            $uri = ($this->uri)($uri);
        }

        return $this->factory->newInstance($uri);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function object(ResourceObject $ro): RequestInterface
    {
        return new Request($this->invoker, $ro, $this->method);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use createRequest() instead
     */
    #[Override]
    public function uri($uri): RequestInterface
    {
        $method = $this->method; // save method, this may change on newInstance(), this is singleton!
        $this->method = 'get';
        $ro = $this->newInstance($uri);
        $ro->uri->method = $method;
        $this->request = new Request($this->invoker, $ro, $ro->uri->method, $ro->uri->query, [], $this->linkerProvider);

        return $this->request;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function createRequest(string $method, string $uri, array $query = []): RequestInterface
    {
        $ro = $this->newInstance($uri);
        $ro->uri->method = $method;
        $ro->uri->query = array_merge($ro->uri->query, $query);
        $this->request = new Request($this->invoker, $ro, $method, $ro->uri->query, [], $this->linkerProvider);

        return $this->request;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress NoInterfaceProperties
     * @psalm-suppress MixedMethodCall
     */
    #[Override]
    public function crawl(string $uri, string $linkKey, array $query = []): ResourceObject
    {
        /** @var Request $request */
        $request = $this->createRequest(Request::GET, $uri, $query)->linkCrawl($linkKey);
        $request->in = 'eager';
        $ro = $request->request();
        assert($ro instanceof ResourceObject);

        return $ro;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress NoInterfaceProperties
     * @psalm-suppress MixedMethodCall
     */
    #[Override]
    public function href(string $rel, array $query = [], ResourceObject|null $ro = null): ResourceObject
    {
        $sourceRequest = $ro !== null
            ? new Request($this->invoker, $ro, $ro->uri->method, $ro->uri->query)
            : $this->request;
        [$method, $uri] = $this->anchor->href($rel, $sourceRequest, $query);
        /** @var Request $request */
        $request = $this->createRequest($method, $uri, $query);
        $request->in = 'eager';
        $result = $request->request();
        assert($result instanceof ResourceObject);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function get(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::GET, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function post(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::POST, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function put(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::PUT, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function patch(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::PATCH, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function delete(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::DELETE, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function options(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::OPTIONS, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function head(string $uri, array $query = []): ResourceObject
    {
        return $this->methodUri(Request::HEAD, $uri)($query);
    }

    /** @psalm-suppress DeprecatedMethod */
    private function methodUri(string $method, string $uri): RequestInterface
    {
        $this->method = $method;

        return $this->uri($uri);
    }
}
