<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\ParameterException;
use InvalidArgumentException;
use Override;
use Ray\Di\InjectorInterface;

final class NamedParameter implements NamedParameterInterface
{
    public function __construct(
        private readonly NamedParamMetasInterface $paramMetas,
        private readonly InjectorInterface $injector,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParameterException Thrown when the query parameter is invalid.
     */
    #[Override]
    public function getParameters(callable $callable, array $query): array
    {
        $metas = ($this->paramMetas)($callable);
        $parameters = [];
        foreach ($metas as $varName => $param) {

            /** @psalm-suppress MixedAssignment */
            try {
                $parameters[$varName] = $param($varName, $query, $this->injector);
            } catch (InvalidArgumentException $e) {
                // handle missing query parameter in Ray.InputQuery
                throw new ParameterException($varName, $e->getCode(), $e);
            }
        }

        return $parameters;
    }
}
