<?php

declare(strict_types=1);

namespace BEAR\Resource\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Ray\Di\Di\Qualifier;

/**
 * @Annotation
 * @Target("METHOD")
 * @Qualifier
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD), Qualifier]
final class ContextScheme
{
    /** @var string */
    public $value;

    public function __construct(string $value = '')
    {
        $this->value = $value;
    }
}
