<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use Ray\InputQuery\Attribute\Input;

final class InputWithUntyped
{
    /** @var mixed */
    public $untyped;

    /** @param mixed $untyped */
    public function __construct(
        #[Input] public readonly string $name,
        $untyped,
    ) {
        $this->untyped = $untyped;
    }
}
