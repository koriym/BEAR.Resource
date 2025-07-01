<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;

final class NullLogger implements LoggerInterface
{
    #[Override]
    public function __invoke(ResourceObject $ro): void
    {
    }
}
