<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Annotation\RequestParamInterface;
use BEAR\Resource\Annotation\ResourceParam;
use Override;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\Assisted;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;
use Ray\InputQuery\FileUploadFactoryInterface;
use Ray\InputQuery\InputQueryInterface;
use Ray\WebContextParam\Annotation\AbstractWebContextParam;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * @psalm-import-type ParamMap from Types
 * @psalm-import-type WebContextParamMap from Types
 * @psalm-import-type ReflectionParameterMap from Types
 * @psalm-import-type ObjectList from Types
 */
final readonly class NamedParamMetas implements NamedParamMetasInterface
{
    /** @param InputQueryInterface<object> $inputQuery */
    public function __construct(
        private InputQueryInterface $inputQuery,
        private FileUploadFactoryInterface $factory,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke(callable $callable): array
    {
        // callable is [object, string] but native type doesn't allow array access
        /** @psalm-suppress InvalidArrayAccess, MixedArgument */
        /** @var array{0:object, 1:string} $callable */
        $method = new ReflectionMethod($callable[0], $callable[1]); // @phpstan-ignore-line
        $paramMetas = $this->getAttributeParamMetas($method);

        if (! $paramMetas) {
            $paramMetas = $this->getAnnotationParamMetas($method);
        }

        return $paramMetas;
    }

    /** @return array<string, AssistedWebContextParam|ParamInterface> */
    private function getAnnotationParamMetas(ReflectionMethod $method): array
    {
        $parameters = $method->getParameters();
        $annotations = $method->getAnnotations();
        $assistedNames = $this->getAssistedNames($annotations);
        $webContext = $this->getWebContext($annotations);

        return $this->addNamedParams($parameters, $assistedNames, $webContext);
    }

    /**
     * @return ParamMap
     *
     * @psalm-suppress TooManyTemplateParams $refAttribute
     * @psalm-suppress PossiblyInvalidArrayAssignment
     */
    private function getAttributeParamMetas(ReflectionMethod $method): array
    {
        $parameters = $method->getParameters();
        $names = $valueParams = [];
        foreach ($parameters as $parameter) {
            $refAttribute = $parameter->getAttributes(RequestParamInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($refAttribute) {
                /** @var ?ResourceParam $resourceParam */
                $resourceParam = $refAttribute[0]->newInstance();
                if ($resourceParam instanceof ResourceParam) {
                    $names[$parameter->name] = new AssistedResourceParam($resourceParam);
                    continue;
                }
            }

            $refWebContext = $parameter->getAttributes(AbstractWebContextParam::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($refWebContext) {
                $webParam = $refWebContext[0]->newInstance();
                $default = $this->getDefault($parameter);
                $param = new AssistedWebContextParam($webParam, $default);
                $names[$parameter->name] = $param;
                continue;
            }

            // #[Input]
            $inputAttribute = $parameter->getAttributes(Input::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($inputAttribute) {
                $names[$parameter->name] = new InputParam($this->inputQuery, $parameter);
                continue;
            }

            // #[InputFile]
            $inputFileAttributes = $parameter->getAttributes(InputFile::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($inputFileAttributes) {
                $this->setInputFileParam($parameter, $inputFileAttributes, $names);
                continue;
            }

            $valueParams[$parameter->name] = $parameter;
        }

        $names = $this->getNames($names, $valueParams);

        return $names;
    }

    /**
     * @param ObjectList $annotations
     *
     * @return ParamMap
     */
    private function getAssistedNames(array $annotations): array
    {
        $names = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ResourceParam) {
                $names[$annotation->param] = new AssistedResourceParam($annotation);
            }

            if (! ($annotation instanceof Assisted)) {
                continue;
            }

            // @codeCoverageIgnoreStart
            $names = $this->setAssistedAnnotation($names, $annotation); // BC for annotation
            // @codeCoverageIgnoreEnd
        }

        return $names;
    }

    /**
     * @param ObjectList $annotations
     *
     * @return WebContextParamMap
     *
     * @codeCoverageIgnore BC for annotation
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress UndefinedPropertyFetch
     */
    private function getWebContext(array $annotations): array
    {
        $webcontext = [];
        foreach ($annotations as $annotation) {
            if (! ($annotation instanceof AbstractWebContextParam)) {
                continue;
            }

            $webcontext[$annotation->param] = $annotation;
        }

        return $webcontext;
    }

    /**
     * @param ParamMap $names
     *
     * @return ParamMap
     *
     * @codeCoverageIgnore BC for annotation
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedAssignment
     * @psalm-suppress UndefinedPropertyFetch
     */
    private function setAssistedAnnotation(array $names, Assisted $assisted): array
    {
        foreach ($assisted->values as $assistedParam) {
            $names[$assistedParam] = new AssistedParam();
        }

        return $names;
    }

    /**
     * @param list<ReflectionParameter> $parameters
     * @param ParamMap                  $assistedNames
     * @param WebContextParamMap        $webcontext
     *
     * @return (AssistedWebContextParam|ParamInterface)[]
     * @psalm-return array<string, AssistedWebContextParam|ParamInterface>
     *
     * @psalm-suppress InvalidArgument
     */
    private function addNamedParams(array $parameters, array $assistedNames, array $webcontext): array
    {
        $names = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->name;
            if (isset($assistedNames[$name])) {
                $names[$name] = $assistedNames[$parameter->name];

                continue;
            }

            if (isset($webcontext[$name])) {
                $default = $this->getDefault($parameter);
                $names[$name] = new AssistedWebContextParam($webcontext[$name], $default);

                continue;
            }

            $names[$name] = $this->getParam($parameter);
        }

        return $names;
    }

    /**
     * @param array<ReflectionAttribute<InputFile>> $inputFileAttributes
     * @param ParamMap                              $names
     */
    private function setInputFileParam(ReflectionParameter $parameter, array $inputFileAttributes, array &$names): void
    {
        $type = $parameter->getType();
        $isArray = $type instanceof ReflectionNamedType && $type->isBuiltin() && $type->getName() === 'array';
        if ($isArray) {
            $names[$parameter->name] = new InputFormsParam($this->factory, $parameter, $inputFileAttributes);

            return;
        }

        $names[$parameter->name] = new InputFormParam($this->factory, $parameter, $inputFileAttributes);
    }

    /** @psalm-return DefaultParam<mixed>|NoDefaultParam */
    private function getDefault(ReflectionParameter $parameter): DefaultParam|NoDefaultParam
    {
        return $parameter->isDefaultValueAvailable() === true ? new DefaultParam($parameter->getDefaultValue()) : new NoDefaultParam();
    }

    /**
     * @param ParamMap               $names
     * @param ReflectionParameterMap $valueParams
     *
     * @return ParamMap
     */
    private function getNames(array $names, array $valueParams): array
    {
        // if there is more than single attributes
        if ($names) {
            foreach ($valueParams as $paramName => $valueParam) {
                $names[$paramName] = $this->getParam($valueParam);
            }
        }

        return $names;
    }

    /**
     * @return ClassParam|OptionalParam|RequiredParam
     * @psalm-return ClassParam|OptionalParam<mixed>|RequiredParam
     */
    private function getParam(ReflectionParameter $parameter): ParamInterface
    {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return new ClassParam($type, $parameter);
        }

        return $parameter->isDefaultValueAvailable() === true ? new OptionalParam($parameter->getDefaultValue()) : new RequiredParam();
    }
}
