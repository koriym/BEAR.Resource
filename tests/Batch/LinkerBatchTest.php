<?php

declare(strict_types=1);

namespace BEAR\Resource\Batch;

use BEAR\Resource\AppAdapter;
use BEAR\Resource\Factory;
use BEAR\Resource\FakeRo;
use BEAR\Resource\InvokerFactory;
use BEAR\Resource\Linker;
use BEAR\Resource\LinkType;
use BEAR\Resource\Request;
use BEAR\Resource\SchemeCollection;
use BEAR\Resource\UriFactory;
use FakeVendor\Sandbox\BatchResolver\LikeBatchResolver;
use FakeVendor\Sandbox\Resource\App\Batch\Article;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class LinkerBatchTest extends TestCase
{
    private Linker $linker;
    private Request $request;

    protected function setUp(): void
    {
        LikeBatchResolver::reset();

        $invoker = (new InvokerFactory())();
        $injector = new Injector();
        $schemeCollection = (new SchemeCollection())
            ->scheme('app')
            ->host('self')
            ->toAdapter(new AppAdapter($injector, 'FakeVendor\Sandbox'));

        $batchResolverFactory = new BatchResolverFactory($injector);

        $this->linker = new Linker(
            $invoker,
            new Factory($schemeCollection, new UriFactory()),
            $batchResolverFactory,
        );
    }

    public function testBatchCrawl(): void
    {
        $invoker = (new InvokerFactory())();
        $this->request = new Request(
            $invoker,
            (new FakeRo())(new Article()),
            Request::GET,
            ['id' => 1],
            [new LinkType('comment-tree', LinkType::CRAWL_LINK)],
        );

        $result = $this->linker->invoke($this->request);

        // Verify structure
        $this->assertSame(1, $result->body['id']);
        $this->assertSame('Article 1', $result->body['title']);
        $this->assertArrayHasKey('comment', $result->body);

        // Verify comments have likes
        $comments = $result->body['comment'];
        $this->assertCount(3, $comments);

        // Comment 10 should have 2 likes
        $this->assertSame(10, $comments[0]['id']);
        $this->assertArrayHasKey('like', $comments[0]);
        $this->assertCount(2, $comments[0]['like']);
        $this->assertSame(100, $comments[0]['like'][0]['id']);
        $this->assertSame('user1', $comments[0]['like'][0]['user_id']);

        // Comment 11 should have 1 like
        $this->assertSame(11, $comments[1]['id']);
        $this->assertCount(1, $comments[1]['like']);

        // Comment 12 should have 3 likes
        $this->assertSame(12, $comments[2]['id']);
        $this->assertCount(3, $comments[2]['like']);

        // BatchResolver should be called only once (not 3 times for 3 comments)
        $this->assertSame(1, LikeBatchResolver::$callCount);
    }

    public function testBatchCrawlWithoutBatchFactory(): void
    {
        // Create linker without batch resolver factory
        $invoker = (new InvokerFactory())();
        $schemeCollection = (new SchemeCollection())
            ->scheme('app')
            ->host('self')
            ->toAdapter(new AppAdapter(new Injector(), 'FakeVendor\Sandbox'));

        $linkerWithoutBatch = new Linker(
            $invoker,
            new Factory($schemeCollection, new UriFactory()),
            null, // No batch factory
        );

        $this->request = new Request(
            $invoker,
            (new FakeRo())(new Article()),
            Request::GET,
            ['id' => 1],
            [new LinkType('comment-tree', LinkType::CRAWL_LINK)],
        );

        // Should still work but fall back to individual requests
        $result = $linkerWithoutBatch->invoke($this->request);

        // Verify structure is still correct
        $this->assertSame(1, $result->body['id']);
        $this->assertArrayHasKey('comment', $result->body);

        // BatchResolver should NOT be called
        $this->assertSame(0, LikeBatchResolver::$callCount);
    }
}
