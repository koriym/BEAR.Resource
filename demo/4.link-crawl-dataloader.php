<?php

declare(strict_types=1);

/**
 * DataLoader Demo - Solving N+1 problem with bulk loading
 *
 * This demo shows how to use DataLoader to reduce the number of resource requests
 * during Crawl traversal. Instead of making N+1 requests, DataLoader batches them
 * into a single request.
 *
 * Without DataLoader:
 *   Article → Comment × 3 → Like (3 separate requests)
 *
 * With DataLoader:
 *   Article → Comment × 3 → Like (1 batched request)
 */

namespace MyVendor\DataLoaderDemo\DataLoader {

    require dirname(__DIR__) . '/vendor/autoload.php';

    use BEAR\Resource\DataLoader\DataLoaderInterface;
    use BEAR\Resource\DataLoader\Requests;
    use BEAR\Resource\DataLoader\Results;

    /**
     * DataLoader for Like resources
     *
     * Receives multiple URIs, fetches all data in one query, and maps results back to URIs.
     */
    class LikeDataLoader implements DataLoaderInterface
    {
        /** @var array<int, array<int, array{id: int, comment_id: int, user_id: string}>> */
        private static array $data = [
            10 => [
                ['id' => 100, 'comment_id' => 10, 'user_id' => 'alice'],
                ['id' => 101, 'comment_id' => 10, 'user_id' => 'bob'],
            ],
            11 => [
                ['id' => 110, 'comment_id' => 11, 'user_id' => 'charlie'],
            ],
            12 => [
                ['id' => 120, 'comment_id' => 12, 'user_id' => 'dave'],
                ['id' => 121, 'comment_id' => 12, 'user_id' => 'eve'],
                ['id' => 122, 'comment_id' => 12, 'user_id' => 'frank'],
            ],
        ];

        public function __invoke(Requests $requests): Results
        {
            echo "  [DataLoader] Called once with URIs:\n";
            foreach ($requests->uris() as $uri) {
                echo "    - {$uri}\n";
            }

            // 1. Extract comment_ids from URIs
            $commentIds = $requests->getQueryParam('comment_id');

            // 2. Bulk fetch: SELECT * FROM likes WHERE comment_id IN (...)
            $rows = [];
            foreach ($commentIds as $commentId) {
                $likes = self::$data[(int) $commentId] ?? [];
                $rows = array_merge($rows, $likes);
            }

            // 3. Map results back to URIs
            return $requests->mapResults($rows, 'comment_id');
        }
    }
}

namespace MyVendor\DataLoaderDemo\Resource\App {

    use BEAR\Resource\Annotation\Link;
    use BEAR\Resource\ResourceObject;
    use MyVendor\DataLoaderDemo\DataLoader\LikeDataLoader;

    class Article extends ResourceObject
    {
        #[Link(crawl: 'tree', rel: 'comment', href: 'app://self/comment?article_id={id}')]
        public function onGet(int $id = 0): static
        {
            $this->body = [
                'id' => $id,
                'title' => 'Article ' . $id,
            ];

            return $this;
        }
    }

    class Comment extends ResourceObject
    {
        /** @var array<int, array<int, array{id: int, article_id: int, body: string}>> */
        private static array $data = [
            1 => [
                ['id' => 10, 'article_id' => 1, 'body' => 'Great article!'],
                ['id' => 11, 'article_id' => 1, 'body' => 'Thanks for sharing'],
                ['id' => 12, 'article_id' => 1, 'body' => 'Very helpful'],
            ],
        ];

        /**
         * Note: dataLoader parameter specifies the DataLoader class to use
         * This batches all Like requests into a single DataLoader call
         */
        #[Link(crawl: 'tree', rel: 'like', href: 'app://self/like?comment_id={id}', dataLoader: LikeDataLoader::class)]
        public function onGet(int $article_id = 0): static
        {
            $this->body = self::$data[$article_id] ?? [];

            return $this;
        }
    }

    class Like extends ResourceObject
    {
        public function onGet(int $comment_id = 0): static
        {
            // This is called only when DataLoader is not available
            echo "  [Like::onGet] Called for comment_id={$comment_id}\n";
            $this->body = [];

            return $this;
        }
    }
}

namespace MyVendor\DataLoaderDemo\Module {

    use BEAR\Resource\Module\DataLoaderModule as BearDataLoaderModule;
    use BEAR\Resource\Module\ResourceModule;
    use Ray\Di\AbstractModule;

    class DataLoaderModule extends AbstractModule
    {
        protected function configure(): void
        {
            $this->install(new ResourceModule('MyVendor\DataLoaderDemo'));
            $this->install(new BearDataLoaderModule());
        }
    }
}

namespace Main {

    use BEAR\Resource\ResourceInterface;
    use MyVendor\DataLoaderDemo\Module\DataLoaderModule;
    use Ray\Di\Injector;

    echo "=== DataLoader Demo ===\n\n";
    echo "Fetching Article with Comments and Likes using DataLoader...\n\n";

    $resource = (new Injector(new DataLoaderModule(), __DIR__ . '/tmp'))->getInstance(ResourceInterface::class);
    $article = $resource->get->uri('app://self/article')->withQuery(['id' => 1])->linkCrawl('tree')();

    echo "\nResult:\n";
    echo json_encode($article->body, JSON_PRETTY_PRINT) . PHP_EOL;
}

// Expected output:
// === DataLoader Demo ===
//
// Fetching Article with Comments and Likes using DataLoader...
//
//   [DataLoader] Called once with URIs:
//     - app://self/like?comment_id=10
//     - app://self/like?comment_id=11
//     - app://self/like?comment_id=12
//
// Result:
// {
//     "id": 1,
//     "title": "Article 1",
//     "comment": [
//         {
//             "id": 10,
//             "article_id": 1,
//             "body": "Great article!",
//             "like": [
//                 {"id": 100, "comment_id": 10, "user_id": "alice"},
//                 {"id": 101, "comment_id": 10, "user_id": "bob"}
//             ]
//         },
//         {
//             "id": 11,
//             "article_id": 1,
//             "body": "Thanks for sharing",
//             "like": [
//                 {"id": 110, "comment_id": 11, "user_id": "charlie"}
//             ]
//         },
//         {
//             "id": 12,
//             "article_id": 1,
//             "body": "Very helpful",
//             "like": [
//                 {"id": 120, "comment_id": 12, "user_id": "dave"},
//                 {"id": 121, "comment_id": 12, "user_id": "eve"},
//                 {"id": 122, "comment_id": 12, "user_id": "frank"}
//             ]
//         }
//     ]
// }
