<?php

declare(strict_types=1);

namespace BEAR\Resource;

/**
 * Stateless resource client interface - coroutine safe
 *
 * Unlike ResourceInterface which supports a stateful fluent interface,
 * this interface provides a purely stateless API suitable for concurrent usage.
 *
 * @psalm-import-type Query from Types
 */
interface ResourceClientInterface
{
    /**
     * Return new resource object instance
     *
     * @param AbstractUri|string $uri
     */
    public function newInstance($uri): ResourceObject;

    /**
     * Create a new request (coroutine-safe)
     *
     * @param Query $query
     */
    public function newRequest(string $method, string $uri, array $query = []): RequestInterface;

    /**
     * Invoke GET request
     *
     * @param Query $query
     */
    public function get(string $uri, array $query = []): ResourceObject;

    /**
     * Invoke POST request
     *
     * @param Query $query
     */
    public function post(string $uri, array $query = []): ResourceObject;

    /**
     * Invoke PUT request
     *
     * @param Query $query
     */
    public function put(string $uri, array $query = []): ResourceObject;

    /**
     * Invoke PATCH request
     *
     * @param Query $query
     */
    public function patch(string $uri, array $query = []): ResourceObject;

    /**
     * Invoke DELETE request
     *
     * @param Query $query
     */
    public function delete(string $uri, array $query = []): ResourceObject;

    /**
     * Invoke HEAD request
     *
     * @param Query $query
     */
    public function head(string $uri, array $query = []): ResourceObject;

    /**
     * Invoke OPTIONS request
     *
     * @param Query $query
     */
    public function options(string $uri, array $query = []): ResourceObject;

    /**
     * Hyper reference (Hypertext As The Engine Of Application State)
     *
     * @param Query $query
     */
    public function href(string $rel, array $query, ResourceObject $ro): ResourceObject;

    /**
     * Execute a GET request with link crawl
     *
     * @param Query $query
     */
    public function crawl(string $uri, string $linkKey, array $query = []): ResourceObject;
}
