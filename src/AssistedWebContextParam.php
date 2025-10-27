<?php

declare(strict_types=1);

namespace BEAR\Resource;

use Override;
use Ray\Di\InjectorInterface;
use Ray\WebContextParam\Annotation\AbstractWebContextParam;

use function assert;
use function is_string;

/** @psalm-import-type Query from Types */
final class AssistedWebContextParam implements ParamInterface
{
    /**
     * $GLOBALS for testing
     *
     * @var array<string, Query>
     */
    private static array $globals = [];

    public function __construct(
        private readonly AbstractWebContextParam $webContextParam,
        private readonly ParamInterface $defaultParam,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke(string $varName, array $query, InjectorInterface $injector)
    {
        $superGlobals = static::$globals ?: $GLOBALS;
        /** @var array<string, Query> $superGlobals */
        $webContextParam = $this->webContextParam;
        assert(is_string($webContextParam::GLOBAL_KEY));
        /** @psalm-suppress MixedArrayOffset */
        $phpWebContext = $superGlobals[$webContextParam::GLOBAL_KEY];

        return $phpWebContext[$this->webContextParam->key] ?? ($this->defaultParam)($varName, $query, $injector);
    }

    /** @param array<string, Query> $globals */
    public static function setSuperGlobalsOnlyForTestingPurpose(array $globals): void
    {
        self::$globals = $globals;
    }
}
