<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\LinkType;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class DataLoaderProcessorTest extends TestCase
{
    private DataLoaderProcessor $processor;

    protected function setUp(): void
    {
        $factory = new DataLoaderFactory(new Injector());
        $this->processor = new DataLoaderProcessor($factory);
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
}
