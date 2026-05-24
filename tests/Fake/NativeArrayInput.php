<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use Ray\InputQuery\Attribute\Input;

final readonly class NativeArrayInput
{
    /** @param list<int> $tagIds */
    public function __construct(
        #[Input]
        public array $tagIds = [],
    ) {
    }
}
