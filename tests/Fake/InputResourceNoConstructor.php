<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class InputResourceNoConstructor extends ResourceObject
{
    public function onPost(#[Input] InputNoConstructor $input): static
    {
        $this->body = [];

        return $this;
    }
}
