<?php

declare(strict_types=1);

namespace Ray\WebContextParam\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class EnvParam extends AbstractWebContextParam
{
    public const GLOBAL_KEY = '_ENV';
}
