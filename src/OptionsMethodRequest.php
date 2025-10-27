<?php

declare(strict_types=1);

namespace BEAR\Resource;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

use function assert;
use function is_array;
use function is_string;
use function method_exists;

/**
 * @psalm-import-type OptionsResponse from Types
 * @psalm-import-type InsMap from Types
 * @psalm-import-type ParameterMetadata from Types
 * @psalm-import-type ParametersMap from Types
 * @psalm-import-type RequiredParameters from Types
 */
final class OptionsMethodRequest
{
    /**
     * @param ParametersMap $paramDoc
     * @param InsMap        $ins
     *
     * @return OptionsResponse
     */
    public function __invoke(ReflectionMethod $method, array $paramDoc, array $ins): array
    {
        return $this->getParamMetas($method->getParameters(), $paramDoc, $ins);
    }

    /**
     * @param ParametersMap $paramDoc
     *
     * @psalm-suppress RedundantCondition for BC
     */
    private function getParameterType(ReflectionParameter $parameter, array $paramDoc, string $name): string|null
    {
        /** @phpstan-ignore function.alreadyNarrowedType */
        $hasType = method_exists($parameter, 'getType') && $parameter->getType();
        if ($hasType) {
            return $this->getType($parameter);
        }

        return $paramDoc[$name]['type'] ?? null;
    }

    /**
     * @param array<ReflectionParameter> $parameters
     * @param ParametersMap              $paramDoc
     * @param InsMap                     $ins
     *
     * @return OptionsResponse
     */
    private function getParamMetas(array $parameters, array $paramDoc, array $ins): array
    {
        foreach ($parameters as $parameter) {
            $name = (string) $parameter->name;
            if (isset($ins[$name])) {
                $paramDoc[$name]['in'] = $ins[$parameter->name];
            }

            if (! isset($paramDoc[$parameter->name])) {
                $paramDoc[$name] = [];
            }

            $paramDoc = $this->paramType($paramDoc, $parameter);
            $paramDoc = $this->paramDefault($paramDoc, $parameter);
        }

        $required = $this->getRequired($parameters);

        return $this->setParamMetas($paramDoc, $required);
    }

    /**
     * @param array<ReflectionParameter> $parameters
     *
     * @return RequiredParameters
     */
    private function getRequired(array $parameters): array
    {
        $required = [];
        foreach ($parameters as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }

            $required[] = $parameter->name;
        }

        return $required;
    }

    /**
     * @param ParametersMap $paramDoc
     *
     * @return ParametersMap
     *
     * @throws ReflectionException
     */
    private function paramDefault(array $paramDoc, ReflectionParameter $parameter): array
    {
        $hasDefault = $parameter->isDefaultValueAvailable() && $parameter->getDefaultValue() !== null;
        if ($hasDefault) {
            $default = $parameter->getDefaultValue();
            $paramDoc[(string) $parameter->name]['default'] = is_array($default) ? '[]' : (string) $parameter->getDefaultValue(); // @phpstan-ignore-lines
        }

        return $paramDoc;
    }

    /**
     * @param ParametersMap $paramDoc
     *
     * @return ParametersMap
     */
    private function paramType(array $paramDoc, ReflectionParameter $parameter): array
    {
        $type = $this->getParameterType($parameter, $paramDoc, $parameter->name);
        if (is_string($type)) {
            $paramDoc[(string) $parameter->name]['type'] = $type; // override type parameter by reflection over phpdoc param type
        }

        return $paramDoc;
    }

    private function getType(ReflectionParameter $parameter): string
    {
        $namedType = $parameter->getType();
        assert($namedType instanceof ReflectionNamedType);
        $type = $namedType->getName();
        if ($type === 'int') {
            $type = 'integer';
        }

        return $type;
    }

    /**
     * @param ParametersMap      $paramDoc
     * @param RequiredParameters $required
     *
     * @return OptionsResponse
     */
    private function setParamMetas(array $paramDoc, array $required): array
    {
        $paramMetas = [];
        if ((bool) $paramDoc) {
            $paramMetas['parameters'] = $paramDoc;
        }

        if ((bool) $required) {
            $paramMetas['required'] = $required;
        }

        return $paramMetas;
    }
}
