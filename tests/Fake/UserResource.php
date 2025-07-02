<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class UserResource extends ResourceObject
{
    public function onPost(#[Input] UserInput $user): static
    {
        $this->body = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        return $this;
    }
}