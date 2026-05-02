<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\ParameterException;
use BEAR\Resource\Fake\NativeArrayResource;
use BEAR\Resource\Fake\UserInput;
use BEAR\Resource\Fake\UserResource;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\FileUploadFactory;
use Ray\InputQuery\InputQuery;
use Ray\InputQuery\InputQueryInterface;
use ReflectionMethod;
use TypeError;

class InputQueryIntegrationTest extends TestCase
{
    public function testNamedParamMetasDetectsInputAttribute(): void
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());
        $namedParamMetas = new NamedParamMetas($inputQuery, new FileUploadFactory());
        $callable = [new UserResource(), 'onPost'];

        $metas = $namedParamMetas($callable);

        $this->assertArrayHasKey('user', $metas);
        $this->assertInstanceOf(InputParam::class, $metas['user']);
    }

    public function testInputParamInvocation(): void
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());

        // Get the reflection parameter for UserInput
        $reflection = new ReflectionMethod(UserResource::class, 'onPost');
        $parameter = $reflection->getParameters()[0]; // First parameter is UserInput

        $inputParam = new InputParam($inputQuery, $parameter);

        $query = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = $inputParam('user', $query, $injector);

        $this->assertInstanceOf(UserInput::class, $result);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function testEndToEndIntegration(): void
    {
        // Test complete flow: NamedParamMetas -> NamedParameter -> InputParam
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());
        $namedParamMetas = new NamedParamMetas($inputQuery, new FileUploadFactory());
        $namedParameter = new NamedParameter($namedParamMetas, $injector);

        $callable = [new UserResource(), 'onPost'];
        $query = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ];

        $parameters = $namedParameter->getParameters($callable, $query);

        $this->assertArrayHasKey('user', $parameters);
        $this->assertInstanceOf(UserInput::class, $parameters['user']);
        $this->assertSame('Jane Smith', $parameters['user']->name);
        $this->assertSame('jane@example.com', $parameters['user']->email);
    }

    public function testNativeArrayInputEndToEndIntegration(): void
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());
        $namedParamMetas = new NamedParamMetas($inputQuery, new FileUploadFactory());
        $namedParameter = new NamedParameter($namedParamMetas, $injector);
        $resource = new NativeArrayResource();

        $parameters = $namedParameter->getParameters([$resource, 'onPost'], ['tagIds' => [1, 2]]);
        $ro = $resource->onPost(...$parameters);

        $this->assertSame(['tagIds' => [1, 2]], $ro->body);
    }

    public function testInputQueryInvalidArgumentThrowsParameterExceptionWithBadRequestCode(): void
    {
        $injector = new Injector();
        $inputQuery = new class implements InputQueryInterface {
            public function getArguments(ReflectionMethod $method, array $query): array
            {
                unset($method, $query);

                return [];
            }

            public function newInstance(string $class, array $query): object
            {
                unset($class, $query);

                throw new InvalidArgumentException();
            }
        };
        $namedParamMetas = new NamedParamMetas($inputQuery, new FileUploadFactory());
        $namedParameter = new NamedParameter($namedParamMetas, $injector);
        $resource = new NativeArrayResource();

        try {
            $namedParameter->getParameters([$resource, 'onPost'], ['tagIds' => 1]);
            $this->fail('Expected ParameterException for invalid input query.');
        } catch (ParameterException $e) {
            $this->assertSame(Code::BAD_REQUEST, $e->getCode());
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
        }
    }

    public function testNamedParameterPropagatesHydrationTypeError(): void
    {
        $injector = new Injector();
        $inputQuery = new class implements InputQueryInterface {
            public function getArguments(ReflectionMethod $method, array $query): array
            {
                unset($method, $query);

                return [];
            }

            public function newInstance(string $class, array $query): object
            {
                unset($class, $query);

                throw new TypeError('typed DTO hydration failed');
            }
        };
        $namedParamMetas = new NamedParamMetas($inputQuery, new FileUploadFactory());
        $namedParameter = new NamedParameter($namedParamMetas, $injector);
        $resource = new NativeArrayResource();

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('typed DTO hydration failed');

        $namedParameter->getParameters([$resource, 'onPost'], []);
    }

    public function testMissingRequiredParameterThrowsException(): void
    {
        // Test with a scalar parameter that is required
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());

        // Create a reflection parameter for a required string parameter
        $testMethod = new class {
            public function testMethod(#[Input]
            string $requiredParam,): void
            {
            }
        };

        $reflection = new ReflectionMethod($testMethod, 'testMethod');
        $parameter = $reflection->getParameters()[0];

        $inputParam = new InputParam($inputQuery, $parameter);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: requiredParam');

        $inputParam('requiredParam', [], $injector);
    }

    public function testOptionalParameterReturnsNull(): void
    {
        // Test with a nullable parameter
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());

        $testMethod = new class {
            public function testMethod(#[Input]
            string|null $optionalParam,): void
            {
            }
        };

        $reflection = new ReflectionMethod($testMethod, 'testMethod');
        $parameter = $reflection->getParameters()[0];

        $inputParam = new InputParam($inputQuery, $parameter);

        $result = $inputParam('optionalParam', [], $injector);

        $this->assertNull($result);
    }

    public function testParameterWithDefaultValueReturnsNull(): void
    {
        // Test with a parameter that has a default value
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());

        $testMethod = new class {
            public function testMethod(#[Input]
            string $paramWithDefault = 'defaultValue',): void
            {
            }
        };

        $reflection = new ReflectionMethod($testMethod, 'testMethod');
        $parameter = $reflection->getParameters()[0];

        $inputParam = new InputParam($inputQuery, $parameter);

        // Should return null for missing parameter with default value (not required)
        $result = $inputParam('paramWithDefault', [], $injector);

        $this->assertNull($result);
    }

    public function testParameterWithValueFromQuery(): void
    {
        // Test that existing values in query are returned correctly
        $injector = new Injector();
        $inputQuery = new InputQuery($injector, new FileUploadFactory());

        $testMethod = new class {
            public function testMethod(#[Input]
            string $param,): void
            {
            }
        };

        $reflection = new ReflectionMethod($testMethod, 'testMethod');
        $parameter = $reflection->getParameters()[0];

        $inputParam = new InputParam($inputQuery, $parameter);

        $result = $inputParam('param', ['param' => 'test_value'], $injector);

        $this->assertSame('test_value', $result);
    }
}
