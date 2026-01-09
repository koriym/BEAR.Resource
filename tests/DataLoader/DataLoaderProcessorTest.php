<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Exception\RowMustContainKeyInDataLoaderException;
use BEAR\Resource\LinkType;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use ReflectionClass;

class DataLoaderProcessorTest extends TestCase
{
    private DataLoaderProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new DataLoaderProcessor(new Injector());
    }

    public function testProcessSkipsNonLinkAnnotations(): void
    {
        $link = new LinkType('test-crawl', LinkType::CRAWL_LINK);
        $bodyList = [['id' => 1]];

        // Mix of Link and non-Link annotations
        $annotations = [
            new Embed(rel: 'something', src: 'app://self/test'),
            new Link(crawl: 'test-crawl', rel: 'items', href: 'app://self/item?id={id}'),
        ];

        // Should not throw, should skip Embed annotation
        $this->processor->process($annotations, $link, $bodyList);

        // No assertion needed - we just verify it doesn't crash
        $this->assertTrue(true);
    }

    public function testProcessSkipsLinkWithDifferentCrawlKey(): void
    {
        $link = new LinkType('my-crawl', LinkType::CRAWL_LINK);
        $bodyList = [['id' => 1]];

        // Link with different crawl key
        $annotations = [
            new Link(crawl: 'other-crawl', rel: 'items', href: 'app://self/item?id={id}'),
        ];

        // Should skip since crawl key doesn't match
        $this->processor->process($annotations, $link, $bodyList);

        // Body should be unchanged (no 'items' key added)
        $this->assertArrayNotHasKey('items', $bodyList[0]);
    }

    public function testProcessSkipsLinkWithoutDataLoader(): void
    {
        $link = new LinkType('test-crawl', LinkType::CRAWL_LINK);
        $bodyList = [['id' => 1]];

        // Link without dataLoader
        $annotations = [
            new Link(crawl: 'test-crawl', rel: 'items', href: 'app://self/item?id={id}'),
        ];

        // Should skip since no dataLoader specified
        $this->processor->process($annotations, $link, $bodyList);

        // Body should be unchanged
        $this->assertArrayNotHasKey('items', $bodyList[0]);
    }

    public function testExtractKeysFromTemplateWithEqualsFormat(): void
    {
        $processor = new DataLoaderProcessor(new Injector());
        $reflection = new ReflectionClass($processor);
        $method = $reflection->getMethod('extractKeysFromTemplate');

        // Single key: param={var}
        $keys = $method->invoke($processor, 'app://self/meta?post_id={id}');
        $this->assertSame(['post_id'], $keys);

        // Multiple keys
        $keys = $method->invoke($processor, 'app://self/translation?post_id={id}&locale={locale}');
        $this->assertSame(['post_id', 'locale'], $keys);

        // Mixed placeholders and literals
        $keys = $method->invoke($processor, 'app://self/meta?post_id={id}&type=full');
        $this->assertSame(['post_id'], $keys);
    }

    public function testExtractKeysFromTemplateWithQueryExpansion(): void
    {
        $processor = new DataLoaderProcessor(new Injector());
        $reflection = new ReflectionClass($processor);
        $method = $reflection->getMethod('extractKeysFromTemplate');

        // Single key: {?var}
        $keys = $method->invoke($processor, 'app://self/meta{?post_id}');
        $this->assertSame(['post_id'], $keys);

        // Multiple keys: {?var1,var2}
        $keys = $method->invoke($processor, 'app://self/payment{?order_id,amount}');
        $this->assertSame(['order_id', 'amount'], $keys);

        // Query continuation: {&var}
        $keys = $method->invoke($processor, 'app://self/search?q=test{&page,limit}');
        $this->assertSame(['page', 'limit'], $keys);
    }

    public function testExtractKeysFromTemplateWithNoQueryString(): void
    {
        $processor = new DataLoaderProcessor(new Injector());
        $reflection = new ReflectionClass($processor);
        $method = $reflection->getMethod('extractKeysFromTemplate');

        // No query string
        $keys = $method->invoke($processor, 'app://self/meta');
        $this->assertSame([], $keys);
    }

    public function testRowMustContainKeyException(): void
    {
        $this->expectException(RowMustContainKeyInDataLoaderException::class);
        $this->expectExceptionMessage('post_id');

        $processor = new DataLoaderProcessor(new Injector());
        $reflection = new ReflectionClass($processor);
        $method = $reflection->getMethod('buildGroupKey');

        // Row without required key
        $method->invoke($processor, ['id' => 1, 'title' => 'test'], ['post_id']);
    }
}
