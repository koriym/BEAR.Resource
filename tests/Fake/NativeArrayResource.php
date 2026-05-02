<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class NativeArrayResource extends ResourceObject
{
    public function onPost(#[Input] NativeArrayInput $input): static
    {
        $this->body = [
            'tagIds' => $input->tagIds,
        ];

        return $this;
    }
}
