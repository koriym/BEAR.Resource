<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use Ray\InputQuery\Attribute\Input;

final readonly class NullableNativeArrayInput
{
    /** @param list<int>|null $tagIds */
    public function __construct(
        #[Input]
        public array|null $tagIds = null,
    ) {
    }
}
