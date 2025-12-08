<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class InputResourceWithUntyped extends ResourceObject
{
    public function onPost(#[Input] InputWithUntyped $input): static
    {
        $this->body = [
            'name' => $input->name,
            'untyped' => $input->untyped,
        ];

        return $this;
    }
}
