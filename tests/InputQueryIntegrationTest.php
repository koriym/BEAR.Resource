<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Fake\UserInput;
use BEAR\Resource\Fake\UserResource;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\InputQuery;
use ReflectionMethod;

class InputQueryIntegrationTest extends TestCase
{
    public function testNamedParamMetasDetectsInputAttribute(): void
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector);
        $namedParamMetas = new NamedParamMetas($inputQuery);
        $callable = [new UserResource(), 'onPost'];

        $metas = $namedParamMetas($callable);

        $this->assertArrayHasKey('user', $metas);
        $this->assertInstanceOf(InputParam::class, $metas['user']);
    }

    public function testInputParamInvocation(): void
    {
        $injector = new Injector();
        $inputQuery = new InputQuery($injector);

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
        $inputQuery = new InputQuery($injector);
        $namedParamMetas = new NamedParamMetas($inputQuery);
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
}
