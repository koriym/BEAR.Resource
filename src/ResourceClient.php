<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;

use function array_merge;
use function assert;
use function is_string;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Stateless Resource client - coroutine safe
 *
 * This class is stateless and can be safely shared across coroutines.
 * For legacy fluent interface usage ($resource->get->uri()), a deprecation
 * warning is triggered and a new Resource instance is created for fallback.
 *
 * @psalm-import-type Query from Types
 */
final class ResourceClient implements ResourceInterface
{
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
     * Fallback to legacy Resource for deprecated fluent interface
     *
     * @deprecated Use newRequest() or direct method calls instead
     * @psalm-suppress DeprecatedMethod
     */
    public function __get(string $name): Resource
    {
        trigger_error(
            'Fluent interface ($resource->get->uri()) is deprecated. Use newRequest() instead.',
            E_USER_DEPRECATED,
        );

        // Create a new mutable Resource instance for legacy compatibility
        $linker = $this->linkerProvider->get();
        $resource = new Resource($this->factory, $this->invoker, $this->anchor, $linker, $this->uri);

        // Set the method and return for chaining
        return $resource->__get($name);
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
        return new Request($this->invoker, $ro, Request::GET);
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use newRequest() instead
     */
    #[Override]
    public function uri($uri): RequestInterface
    {
        return $this->newRequest(Request::GET, (string) $uri);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function newRequest(string $method, string $uri, array $query = []): RequestInterface
    {
        $linker = $this->linkerProvider->get();
        $ro = $this->newInstance($uri);
        $ro->uri->method = $method;
        $ro->uri->query = array_merge($ro->uri->query, $query);

        return new Request($this->invoker, $ro, $method, $ro->uri->query, [], $linker);
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
        $request = $this->newRequest(Request::GET, $uri, $query)->linkCrawl($linkKey);
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
        assert($ro instanceof ResourceObject, 'ResourceObject is required for ResourceClient::href()');
        $sourceRequest = new Request($this->invoker, $ro, $ro->uri->method, $ro->uri->query);
        [$method, $uri] = $this->anchor->href($rel, $sourceRequest, $query);
        /** @var Request $request */
        $request = $this->newRequest($method, $uri, $query);
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
        return $this->newRequest(Request::GET, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function post(string $uri, array $query = []): ResourceObject
    {
        return $this->newRequest(Request::POST, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function put(string $uri, array $query = []): ResourceObject
    {
        return $this->newRequest(Request::PUT, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function patch(string $uri, array $query = []): ResourceObject
    {
        return $this->newRequest(Request::PATCH, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function delete(string $uri, array $query = []): ResourceObject
    {
        return $this->newRequest(Request::DELETE, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function options(string $uri, array $query = []): ResourceObject
    {
        return $this->newRequest(Request::OPTIONS, $uri)($query);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function head(string $uri, array $query = []): ResourceObject
    {
        return $this->newRequest(Request::HEAD, $uri)($query);
    }
}
