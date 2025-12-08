<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use Ray\InputQuery\Attribute\Input;

final readonly class InputWithDefaults
{
    public function __construct(
        #[Input] public int $id,
        #[Input] public string $name = 'default',
        #[Input] public ?string $optional = null,
    ) {
    }
}
