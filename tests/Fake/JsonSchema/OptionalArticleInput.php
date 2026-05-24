<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema;

use Ray\InputQuery\Attribute\Input;

final readonly class OptionalArticleInput
{
    public function __construct(
        #[Input] public string $title,
        #[Input] public string $slug,
    ) {
    }
}
