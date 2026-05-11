<?php

declare(strict_types=1);

namespace BEAR\Resource;

use LogicException;
use Override;

/**
 * Simulates a lazy/batch decorator like bear/async's AsyncRequest:
 * __toString() returns a pre-rendered JSON view without invoking the
 * underlying resource at all. HalRenderer must drive evaluation via
 * __toString() (i.e. (string) $request) so subclasses get a chance to
 * hook in.
 */
final class FakeLazyRequest extends AbstractRequest
{
    private string $preRenderedView;

    public function __construct(string $view, ResourceObject $resourceObject)
    {
        parent::__construct(new FakeLazyInvoker(), $resourceObject);
        $this->preRenderedView = $view;
    }

    #[Override]
    public function __toString(): string
    {
        // Note: we do NOT call $this->invoke(). The pre-rendered view stands
        // in for whatever the lazy/batch decorator would emit. If HalRenderer
        // bypassed __toString() (e.g. by calling $request() directly), the
        // FakeLazyInvoker would throw and the test would fail.
        return $this->preRenderedView;
    }

    #[Override]
    public function withQuery(array $query): RequestInterface
    {
        throw new LogicException(__METHOD__);
    }

    #[Override]
    public function addQuery(array $query): RequestInterface
    {
        throw new LogicException(__METHOD__);
    }

    #[Override]
    public function toUri(): string
    {
        throw new LogicException(__METHOD__);
    }

    #[Override]
    public function toUriWithMethod(): string
    {
        throw new LogicException(__METHOD__);
    }

    #[Override]
    public function linkSelf(string $linkKey): RequestInterface
    {
        throw new LogicException(__METHOD__);
    }

    #[Override]
    public function linkNew(string $linkKey): RequestInterface
    {
        throw new LogicException(__METHOD__);
    }

    #[Override]
    public function linkCrawl(string $linkKey): RequestInterface
    {
        throw new LogicException(__METHOD__);
    }
}
