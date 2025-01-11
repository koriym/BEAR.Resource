<?php

declare(strict_types=1);

namespace BEAR\Resource;

use BEAR\Resource\Exception\MethodNotAllowedException;

/**
 * @deprecated
 */
final class VoidOptionsRenderer implements RenderInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(ResourceObject $ro): never
    {
        throw new MethodNotAllowedException($ro::class . '::options', 405);
    }
}
