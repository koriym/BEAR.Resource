<?php

declare(strict_types=1);

namespace BEAR\Resource\Annotation;

use Attribute;
use BEAR\Resource\Batch\BatchResolverInterface;
use JsonSerializable;
use Override;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Link implements JsonSerializable
{
    /**
     * Relation to the target resource of the link
     *
     * @var string
     */
    public $rel;

    /**
     * A URI template, as defined by RFC 6570
     *
     * @var string
     */
    public $href;

    /**
     * A method for the Link
     *
     * @var string
     */
    public $method;

    /**
     * A title for the link
     *
     * @var string
     */
    public $title;

    /**
     * Crawl tag ID for crawl request
     *
     * @var string
     */
    public $crawl;

    /**
     * BatchResolver class for batch processing
     *
     * @var class-string<BatchResolverInterface>|null
     */
    public $batch;

    /**
     * @return string[]
     * @psalm-return array{rel: string, href: string, method: string, title?: string}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        $json = [
            'rel' => $this->rel,
            'href' => $this->href,
            'method' => $this->method,
        ];
        if ($this->title) {
            $json += ['title' => $this->title];
        }

        return $json;
    }

    /** @param array{rel?: string, href?: string, method?: string, title?: string, crawl?: string, batch?: class-string<BatchResolverInterface>|null} $values */
    public function __construct(
        array $values = [],
        string $rel = '',
        string $href = '',
        string $method = 'get',
        string $title = '',
        string $crawl = '',
        string|null $batch = null,
    ) {
        $this->rel = $values['rel'] ?? $rel;
        $this->href = $values['href'] ?? $href;
        $this->method = $values['method'] ?? $method;
        $this->title = $values['title'] ?? $title;
        $this->crawl = $values['crawl'] ?? $crawl;
        $this->batch = $values['batch'] ?? $batch;
    }
}
