<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use Ray\InputQuery\Attribute\Input;

final readonly class UserInput
{
    public function __construct(
        #[Input] public string $name,
        #[Input] public string $email
    ) {}
}