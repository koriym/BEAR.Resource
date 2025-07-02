<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;

/** @psalm-import-type Query from Types */
final class NullRequest implements RequestInterface
{
    /** @param Query $query */
    #[Override]
    public function __invoke(array|null $query = null): ResourceObject
    {
        return new NullResourceObject();
    }

    #[Override]
    public function hash(): string
    {
        return '';
    }

    #[Override]
    public function request(): ResourceObject
    {
        return new NullResourceObject();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function withQuery(array $query): RequestInterface
    {
        unset($query);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function addQuery(array $query): RequestInterface
    {
        unset($query);

        return $this;
    }

    #[Override]
    public function toUri(): string
    {
        return (string) new NullUri();
    }

    #[Override]
    public function toUriWithMethod(): string
    {
        return 'get ' . (string) new NullUri();
    }

    /** @return self */
    #[Override]
    public function linkSelf(string $linkKey): RequestInterface
    {
        unset($linkKey);

        return $this;
    }

    /** @return self */
    #[Override]
    public function linkNew(string $linkKey): RequestInterface
    {
        unset($linkKey);

        return $this;
    }

    /** @return self */
    #[Override]
    public function linkCrawl(string $linkKey): RequestInterface
    {
        unset($linkKey);

        return $this;
    }
}
