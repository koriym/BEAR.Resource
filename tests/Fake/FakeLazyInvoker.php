<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;
use RuntimeException;

/**
 * Invoker that throws if reached. Used by FakeLazyRequest to assert that
 * HalRenderer never invokes the underlying resource when its __toString()
 * short-circuits (i.e. the lazy/batch decorator path).
 */
final class FakeLazyInvoker implements InvokerInterface
{
    #[Override]
    public function invoke(AbstractRequest $request): ResourceObject
    {
        throw new RuntimeException(
            'FakeLazyInvoker::invoke() must not be called: a lazy decorator should short-circuit before invoke().',
        );
    }
}
