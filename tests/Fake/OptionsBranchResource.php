<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Ray\InputQuery\Attribute\Input;

final class OptionsBranchResource
{
    public function onPost(#[Input] OptionsBranchInput $input): void
    {
    }

    /** @param list<int> $ids */
    public function onGet(array $ids = []): void
    {
    }
}
