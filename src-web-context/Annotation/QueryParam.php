<?php

namespace Ray\WebContextParam\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class QueryParam extends AbstractWebContextParam
{
    const GLOBAL_KEY = '_GET';
}
