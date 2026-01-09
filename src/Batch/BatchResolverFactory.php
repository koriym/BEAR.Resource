<?php

declare(strict_types=1);

namespace BEAR\Resource\Batch;

use Override;
use Ray\Di\InjectorInterface;

use function assert;

/**
 * Factory for creating BatchResolver instances using Ray.Di injector
 */
final class BatchResolverFactory implements BatchResolverFactoryInterface
{
    public function __construct(
        private readonly InjectorInterface $injector,
    ) {
    }

    /** @param class-string<BatchResolverInterface> $class */
    #[Override]
    public function create(string $class): BatchResolverInterface
    {
        $instance = $this->injector->getInstance($class);
        assert($instance instanceof BatchResolverInterface);

        return $instance;
    }
}
