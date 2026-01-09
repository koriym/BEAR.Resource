<?php

declare(strict_types=1);

namespace BEAR\Resource\DataLoader;

use Override;
use Ray\Di\InjectorInterface;

use function assert;

/**
 * Factory for creating DataLoader instances using Ray.Di injector
 */
final class DataLoaderFactory implements DataLoaderFactoryInterface
{
    public function __construct(
        private readonly InjectorInterface $injector,
    ) {
    }

    /** @param class-string<DataLoaderInterface> $class */
    #[Override]
    public function create(string $class): DataLoaderInterface
    {
        $instance = $this->injector->getInstance($class);
        assert($instance instanceof DataLoaderInterface);

        return $instance;
    }
}
