<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;
use Ray\Di\InjectorInterface;

/** @template T */
final class DefaultParam implements ParamInterface
{
    /** @param T $defaultValue */
    public function __construct(
        private readonly mixed $defaultValue,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke(string $varName, array $query, InjectorInterface $injector)
    {
        unset($varName, $query, $injector);

        return $this->defaultValue;
    }
}
