<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Exception\LinkQueryException;
use BEAR\Resource\Exception\LinkRelException;
use BEAR\Resource\Exception\MethodException;
use BEAR\Resource\Exception\UriException;
use Override;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;
use ReflectionMethod;

use function array_filter;
use function array_keys;
use function array_map;
use function array_pop;
use function assert;
use function count;
use function is_array;
use function is_numeric;
use function ucfirst;
use function uri_template;

/**
 * @psalm-import-type Body from Types
 * @psalm-import-type BodyList from Types
 * @psalm-import-type BodyOrStringList from Types
 * @psalm-import-type Query from Types
 * @psalm-import-type QueryList from Types
 */
final class Linker implements LinkerInterface
{
    /** @param ProviderInterface<LinkCrawlerInterface> $linkCrawlerProvider */
    public function __construct(
        private readonly InvokerInterface $invoker,
        private readonly FactoryInterface $factory,
        #[Set(LinkCrawlerInterface::class)]
        private readonly ProviderInterface $linkCrawlerProvider,
    ) {
    }

    #[Override]
    public function invoke(AbstractRequest $request)
    {
        $linkCrawler = $this->linkCrawlerProvider->get();

        return $this->invokeRecursive($request, $linkCrawler);
    }

    /**
     * @throws LinkQueryException
     * @throws LinkRelException
     */
    private function invokeRecursive(AbstractRequest $request, LinkCrawlerInterface $linkCrawler): ResourceObject
    {
        $this->invoker->invoke($request);
        $current = clone $request->resourceObject;
        if ($current->code >= Code::BAD_REQUEST) {
            return $current;
        }

        foreach ($request->links as $link) {
            /** @var Body $nextBody */
            $nextBody = $this->annotationLink($link, $current, $request, $linkCrawler)->body;
            $current = $this->nextLink($link, $current, $nextBody);
        }

        return $current;
    }

    /** @param Body $nextResource */
    private function nextLink(LinkType $link, ResourceObject $ro, array $nextResource): ResourceObject
    {
        $nextBody = $nextResource;

        if ($link->type === LinkType::SELF_LINK) {
            $ro->body = $nextBody;

            return $ro;
        }

        if ($link->type === LinkType::NEW_LINK) {
            assert(is_array($ro->body) || $ro->body === null);
            $ro->body[$link->key] = $nextBody;

            return $ro;
        }

        // crawl
        return $ro;
    }

    /**
     * Annotation link
     *
     * @throws MethodException
     * @throws LinkRelException
     * @throws Exception\LinkQueryException
     */
    private function annotationLink(LinkType $link, ResourceObject $current, AbstractRequest $request, LinkCrawlerInterface $linkCrawler): ResourceObject
    {
        if (! is_array($current->body)) {
            throw new Exception\LinkQueryException('Only array is allowed for link in ' . $current::class, 500);
        }

        $annotations = $this->getLinkAnnotations($current, $request->method);
        if ($link->type === LinkType::CRAWL_LINK) {
            return $this->annotationCrawl($annotations, $link, $current, $linkCrawler);
        }

        return $this->annotationRel($annotations, $link, $current);
    }

    /**
     * Get Link annotations from a ResourceObject method using PHP 8 attributes
     *
     * @return list<Link>
     */
    private function getLinkAnnotations(ResourceObject $ro, string $method): array
    {
        $classMethod = 'on' . ucfirst($method);
        $refMethod = new ReflectionMethod($ro, $classMethod);
        $attributes = $refMethod->getAttributes(Link::class);

        return array_map(
            static fn ($attr) => $attr->newInstance(),
            $attributes,
        );
    }

    /**
     * Annotation link (new, self)
     *
     * @param list<Link> $annotations
     *
     * @throws UriException
     * @throws MethodException
     * @throws Exception\LinkQueryException
     * @throws Exception\LinkRelException
     */
    private function annotationRel(array $annotations, LinkType $link, ResourceObject $current): ResourceObject
    {
        foreach ($annotations as $annotation) {
            if ($annotation->rel !== $link->key) {
                continue;
            }

            $uri = uri_template($annotation->href, (array) $current->body);
            $rel = $this->factory->newInstance($uri);
            $query = (new Uri($uri))->query;
            $request = new Request($this->invoker, $rel, Request::GET, $query);

            return $this->invoker->invoke($request);
        }

        throw new LinkRelException("rel:{$link->key} class:" . $current::class, 500);
    }

    /**
     * Link annotation crawl - delegate to LinkCrawlerInterface
     *
     * @param list<Link> $annotations
     */
    private function annotationCrawl(array $annotations, LinkType $link, ResourceObject $current, LinkCrawlerInterface $linkCrawler): ResourceObject
    {
        $isList = $this->isList($current->body);
        /** @var list<array<string, mixed>> $bodyList */
        $bodyList = $isList ? (array) $current->body : [$current->body];

        // Delegate to LinkCrawler
        $linkCrawler->crawl($annotations, $link, $bodyList);

        $current->body = $isList ? $bodyList : $bodyList[0];

        return $current;
    }

    private function isList(mixed $value): bool
    {
        assert(is_array($value));
        /** @var BodyList $list */
        $list = $value;
        /** @var mixed $firstRow */
        $firstRow = array_pop($list);
        $keys = array_keys((array) $firstRow);

        return $this->isSingleColumnList($value, $keys, $list)
            || $this->isMultiColumnMultiRowList($keys, $list)
            || $this->isMultiColumnList($value, $firstRow);
    }

    /**
     * @param list<array-key> $keys
     * @psalm-param BodyList   $list
     */
    private function isMultiColumnMultiRowList(array $keys, array $list): bool
    {
        if ($keys === [0 => 0]) {
            return false;
        }

        foreach ($list as $item) {
            if ($keys !== array_keys((array) $item)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<mixed> $value */
    private function isMultiColumnList(array $value, mixed $firstRow): bool
    {
        return is_array($firstRow) && array_filter(array_keys($value), is_numeric(...)) === array_keys($value);
    }

    /**
     * @param array<mixed>    $value
     * @param list<array-key> $keys
     * @param array<mixed>    $list
     */
    private function isSingleColumnList(array $value, array $keys, array $list): bool
    {
        return (count($value) === 1) && $keys === array_keys($list);
    }
}
