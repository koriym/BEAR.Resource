<?php

declare(strict_types=1);

namespace BEAR\Resource\Fake;

use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

final class InputResourceWithMixedParams extends ResourceObject
{
    public function onPost(#[Input] UserInput $user, string $token): static
    {
        $this->body = [
            'name' => $user->name,
            'email' => $user->email,
            'token' => $token,
        ];

        return $this;
    }
}
