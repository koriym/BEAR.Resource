<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use Ray\InputQuery\Attribute\Input;

final class UserInput
{
    public function __construct(
        #[Input] public readonly string $name,
        #[Input] public readonly string $email
    ) {}
}