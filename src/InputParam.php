<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;
use Ray\Di\InjectorInterface;
use Ray\InputQuery\InputQueryInterface;
use ReflectionNamedType;
use ReflectionParameter;

use function assert;
use function class_exists;

/** @psalm-import-type Query from Types */
final class InputParam implements ParamInterface
{
    /** @param InputQueryInterface<object> $inputQuery */
    public function __construct(
        private readonly InputQueryInterface $inputQuery,
        private readonly ReflectionParameter $parameter,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke(string $varName, array $query, InjectorInterface $injector)
    {
        $type = $this->parameter->getType();
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $inputClass = $type->getName();
            assert(class_exists($inputClass));

            return $this->inputQuery->create($inputClass, $query);
        }

        // For built-in types, return from query directly
        return $query[$varName] ?? null;
    }
}
