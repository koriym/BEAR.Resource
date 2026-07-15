<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Interceptor;

use AssertionError;
use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\Exception\JsonSchemaKeytNotFoundException;
use BEAR\Resource\Interceptor\JsonSchemaInterceptor;
use BEAR\Resource\JsonSchema\ArticleInput;
use BEAR\Resource\JsonSchema\ArticleWithSeoInput;
use BEAR\Resource\JsonSchema\FakeInputDtoResource;
use BEAR\Resource\JsonSchema\FakeUser;
use BEAR\Resource\JsonSchema\FakeView;
use BEAR\Resource\JsonSchema\SeoInput;
use BEAR\Resource\JsonSchemaExceptionNullHandler;
use BEAR\Resource\JsonSchemaRequestExceptionNullHandler;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectiveMethodInvocation;
use ReflectionMethod;

use function assert;
use function dirname;
use function file_put_contents;
use function is_string;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class JsonSchemaInterceptorTest extends TestCase
{
    private JsonSchemaInterceptor $jsonSchemaIntercetor;

    protected function setup(): void
    {
        $fakeDir = dirname(__DIR__, 2);
        $this->jsonSchemaIntercetor = new JsonSchemaInterceptor(
            $fakeDir . '/Fake/json_schema',
            $fakeDir . '/Fake/json_validate',
            new JsonSchemaExceptionNullHandler(),
            new JsonSchemaRequestExceptionNullHandler(),
            'http://example.com/schema/',
        );
    }

    public function testInvalidKeyJsonSchema(): void
    {
        $this->expectException(JsonSchemaKeytNotFoundException::class);
        $object = new FakeUser();
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, 'invalidKey', [], $interceptrs);
        $this->jsonSchemaIntercetor->invoke($invocation);
    }

    /**
     * covers \BEAR\Resource\Interceptor\JsonSchemaInterceptor::getTarget()
     */
    public function testTarget(): void
    {
        $object = new FakeUser();
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, 'bodyKey', [], $interceptrs);
        $ro = $this->jsonSchemaIntercetor->invoke($invocation);
        $this->assertInstanceOf(ResourceObject::class, $object);
    }

    public function testNotJsonView(): void
    {
        $object = new FakeUser();
        $object->view = 'string';
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, 'bodyKey', [], $interceptrs);
        $this->jsonSchemaIntercetor->invoke($invocation);
        $this->assertInstanceOf(ResourceObject::class, $object);
    }

    public function testTargetView(): void
    {
        $object = new FakeView();
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, 'onGet', [20], $interceptrs);
        $ro = $this->jsonSchemaIntercetor->invoke($invocation);
        $this->assertInstanceOf(ResourceObject::class, $ro);
    }

    public function testBodyValidationIsSkippedForCachedRenderedResponse(): void
    {
        $object = new FakeUser();
        $invocation = new ReflectiveMethodInvocation($object, 'onGet', [20], [
            $this->cachedRenderedResponse('{"name":{"firstName":"mucha","lastName":"alfons"},"age":20}'),
        ]);

        $ro = $this->jsonSchemaIntercetor->invoke($invocation);

        $this->assertSame($object, $ro);
        $this->assertSame('<http://example.com/schema/user.json>; rel="describedby"', $ro->headers['Link']);
    }

    public function testValidationRunsWhenBodyIsPresent(): void
    {
        $this->expectException(JsonSchemaException::class);
        $object = new FakeUser();
        $invocation = new ReflectiveMethodInvocation($object, 'onGet', [20], [
            $this->cachedRenderedResponseWithBody('{"age":20}', ['age' => 20]),
        ]);

        $this->jsonSchemaIntercetor->invoke($invocation);
    }

    public function testViewValidationStillRunsForCachedRenderedResponse(): void
    {
        $this->expectException(JsonSchemaException::class);
        $object = new FakeView();
        $invocation = new ReflectiveMethodInvocation($object, 'onGet', [20], [
            $this->cachedRenderedResponse('{"age":20}'),
        ]);

        $this->jsonSchemaIntercetor->invoke($invocation);
    }

    public function testMissingJsonSchemaAttributeTriggersAssertion(): void
    {
        $this->expectException(AssertionError::class);
        $object = new class extends ResourceObject {
            public function onGet(): static
            {
                return $this;
            }
        };
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, 'onGet', [], $interceptrs);

        $this->jsonSchemaIntercetor->invoke($invocation);
    }

    public function testNonObjectSchemaDecodeReturnsNull(): void
    {
        $schemaFile = tempnam(sys_get_temp_dir(), 'non-object-schema-');
        assert(is_string($schemaFile));
        file_put_contents($schemaFile, 'true');

        try {
            $method = new ReflectionMethod(JsonSchemaInterceptor::class, 'schema');

            $this->assertNull($method->invoke($this->jsonSchemaIntercetor, $schemaFile));
        } finally {
            unlink($schemaFile);
        }
    }

    public function testInputDtoParameterIsFlattenedForRequestValidation(): void
    {
        $ro = $this->invokeInputDtoResource('onPost', [new ArticleInput('Hello', 'hello')]);

        $this->assertSame(['slug' => 'hello', 'title' => 'Hello'], $ro->body);
    }

    public function testInputDtoParameterCanBeMixedWithScalarParameter(): void
    {
        $ro = $this->invokeInputDtoResource('onPut', [new ArticleInput('Hello', 'hello'), 'token']);

        $this->assertSame(['extra' => 'token', 'slug' => 'hello', 'title' => 'Hello'], $ro->body);
    }

    public function testNestedInputDtoParameterIsFlattenedForRequestValidation(): void
    {
        $ro = $this->invokeInputDtoResource(
            'onPatch',
            [new ArticleWithSeoInput('Hello', 'hello', new SeoInput('Hello SEO'))],
        );

        $this->assertSame(['metaTitle' => 'Hello SEO', 'slug' => 'hello', 'title' => 'Hello'], $ro->body);
    }

    public function testOptionalInputDtoParameterDefaultIsFlattenedForRequestValidation(): void
    {
        $ro = $this->invokeInputDtoResource('onDelete', []);

        $this->assertSame(['slug' => 'default-title', 'title' => 'Default Title'], $ro->body);
    }

    public function testMultipleInputDtoParametersAreFlattenedForRequestValidation(): void
    {
        $ro = $this->invokeInputDtoResource('onOptions', [new ArticleInput('Hello', 'hello'), new SeoInput('Hello SEO')]);

        $this->assertSame(['metaTitle' => 'Hello SEO', 'slug' => 'hello', 'title' => 'Hello'], $ro->body);
    }

    /** @param list<mixed> $arguments */
    private function invokeInputDtoResource(string $method, array $arguments): ResourceObject
    {
        $object = new FakeInputDtoResource();
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, $method, $arguments, $interceptrs);

        return $this->jsonSchemaIntercetor->invoke($invocation);
    }

    private function cachedRenderedResponse(string $view): MethodInterceptor
    {
        return $this->renderedResponse($view);
    }

    private function cachedRenderedResponseWithBody(string $view, mixed $body): MethodInterceptor
    {
        return $this->renderedResponse($view, $body, true);
    }

    private function renderedResponse(string $view, mixed $body = null, bool $hasBody = false): MethodInterceptor
    {
        return new class ($view, $body, $hasBody) implements MethodInterceptor {
            public function __construct(
                private readonly string $view,
                private readonly mixed $body,
                private readonly bool $hasBody,
            ) {
            }

            public function invoke(MethodInvocation $invocation): ResourceObject
            {
                $ro = $invocation->getThis();
                assert($ro instanceof ResourceObject);
                $ro->headers['ETag'] = '"cached"';
                $ro->view = $this->view;
                if ($this->hasBody) {
                    $ro->body = $this->body;
                }

                return $ro;
            }
        };
    }
}
