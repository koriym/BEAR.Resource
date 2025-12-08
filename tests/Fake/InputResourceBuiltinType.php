<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class InputResourceBuiltinType extends ResourceObject
{
    public function onPost(#[Input] string $name): static
    {
        $this->body = ['name' => $name];

        return $this;
    }
}
