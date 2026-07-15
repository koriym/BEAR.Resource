<?php

declare(strict_types=1);

namespace BEAR\Resource\Exception;

/**
 * Thrown when a resource embedded as `_self` returns no body — e.g. a
 * `#[CacheableResponse]` or `#[DonutCache]` cache hit that restored only
 * the view, not the body. A `_self`-embedded resource must use `#[Cacheable]`
 * (value cache) so the body survives the cache hit.
 *
 * Message format: `"_self" embed of {uri} returned no body. ...`
 */
final class EmbedException extends BadRequestException
{
}
