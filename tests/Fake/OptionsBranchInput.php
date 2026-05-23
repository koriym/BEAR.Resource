<?php

declare(strict_types=1);

namespace BEAR\Resource;

final class OptionsBranchInput
{
    /** @param list<string> $tags */
    public function __construct(
        public array $tags = [],
    ) {
    }
}
