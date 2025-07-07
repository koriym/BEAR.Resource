<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Ray\Di\Injector;
use Ray\InputQuery\FileUploadFactory;
use Ray\InputQuery\InputQuery;

final class InvokerFactory
{
    public function __invoke(string $schemaDir = ''): Invoker
    {
        return new Invoker(
            new PhpClassInvoker(
                new NamedParameter(
                    new NamedParamMetas(new InputQuery(new Injector(), new FileUploadFactory()), new FileUploadFactory()),
                    new Injector(),
                ),
                new ExtraMethodInvoker(
                    new OptionsRenderer(
                        new OptionsMethods(
                            $schemaDir,
                        ),
                    ),
                ),
                new NullLogger(),
            ),
        );
    }
}
