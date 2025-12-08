<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Ray\InputQuery\Attribute\Input;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

use function array_unique;
use function array_values;
use function assert;
use function in_array;
use function is_array;
use function is_string;
use function method_exists;

/**
 * @psalm-import-type OptionsResponse from Types
 * @psalm-import-type InsMap from Types
 * @psalm-import-type ParameterMetadata from Types
 * @psalm-import-type ParametersMap from Types
 * @psalm-import-type RequiredParameterList from Types
 * @psalm-import-type ReflectionParameterList from Types
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
     * @param ReflectionParameterList $parameters
     * @param ParametersMap           $paramDoc
     * @param InsMap                  $ins
     *
     * @return OptionsResponse
     */
    private function getParamMetas(array $parameters, array $paramDoc, array $ins): array
    {
        $expandedParameters = [];
        $expandedRequired = [];
        $expandedParamNames = [];

        foreach ($parameters as $parameter) {
            // Check for #[Input] attribute with object type
            $inputAttributes = $parameter->getAttributes(Input::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($inputAttributes) {
                $inputResult = $this->expandInputParameter($parameter);
                if ($inputResult !== null) {
                    [$inputParamDoc, $inputRequired] = $inputResult;
                    $expandedParameters += $inputParamDoc;
                    $expandedRequired = [...$expandedRequired, ...$inputRequired];
                    $expandedParamNames[] = $parameter->name;
                    continue;
                }
            }

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

        // Merge expanded parameters with regular parameters
        $paramDoc = $expandedParameters + $paramDoc;

        $required = $this->getRequiredWithoutExpandedParams($parameters, $expandedParamNames);
        // Merge expanded required with regular required
        if ($expandedRequired !== []) {
            $required = array_values(array_unique([...$expandedRequired, ...$required]));
        }

        return $this->setParamMetas($paramDoc, $required);
    }

    /**
     * Expand #[Input] parameter to its constructor properties
     *
     * @return array{0: ParametersMap, 1: RequiredParameterList}|null
     */
    private function expandInputParameter(ReflectionParameter $parameter): array|null
    {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $className = $type->getName();
        $refClass = new ReflectionClass($className);
        $constructor = $refClass->getConstructor();
        if ($constructor === null) {
            return null;
        }

        $paramDoc = [];
        $required = [];

        foreach ($constructor->getParameters() as $ctorParam) {
            $name = $ctorParam->getName();
            $paramDoc[$name] = [];

            // Set type
            $ctorType = $ctorParam->getType();
            if ($ctorType instanceof ReflectionNamedType) {
                $typeName = $ctorType->getName();
                if ($typeName === 'int') {
                    $typeName = 'integer';
                }

                $paramDoc[$name]['type'] = $typeName;
            }

            // Set default if available
            if ($ctorParam->isDefaultValueAvailable() && $ctorParam->getDefaultValue() !== null) {
                $default = $ctorParam->getDefaultValue();
                $paramDoc[$name]['default'] = is_array($default) ? '[]' : (string) $default;
            }

            // Check if required
            if ($ctorParam->isOptional()) {
                continue;
            }

            $required[] = $name;
        }

        return [$paramDoc, $required];
    }

    /**
     * Get required parameters excluding successfully expanded #[Input] parameters
     *
     * @param ReflectionParameterList $parameters
     * @param list<string>            $expandedParamNames Names of parameters that were expanded
     *
     * @return RequiredParameterList
     */
    private function getRequiredWithoutExpandedParams(array $parameters, array $expandedParamNames): array
    {
        $required = [];
        foreach ($parameters as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }

            // Skip parameters that were successfully expanded
            if (in_array($parameter->name, $expandedParamNames, true)) {
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
     * @param ParametersMap         $paramDoc
     * @param RequiredParameterList $required
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
