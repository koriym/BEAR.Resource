<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;

final class NullReverseLinker implements ReverseLinkerInterface
{
    /** @param array<string, mixed> $query */
    #[Override]
    public function __invoke(string $uri, array $query): string
    {
        return $uri;
    }
}
