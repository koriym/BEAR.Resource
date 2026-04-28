<?php

declare(strict_types=1);

namespace BEAR\Resource\JsonSchema\Interceptor;

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
use Ray\Aop\ReflectiveMethodInvocation;

use function dirname;

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

    /** @param list<mixed> $arguments */
    private function invokeInputDtoResource(string $method, array $arguments): ResourceObject
    {
        $object = new FakeInputDtoResource();
        /** @var array<MethodInterceptor> $interceptrs */
        $interceptrs = [$this->jsonSchemaIntercetor];
        $invocation = new ReflectiveMethodInvocation($object, $method, $arguments, $interceptrs);

        return $this->jsonSchemaIntercetor->invoke($invocation);
    }
}
