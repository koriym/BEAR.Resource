<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class InputResourceWithDefaults extends ResourceObject
{
    public function onPost(#[Input] InputWithDefaults $input): static
    {
        $this->body = [
            'id' => $input->id,
            'name' => $input->name,
            'optional' => $input->optional,
        ];

        return $this;
    }
}
