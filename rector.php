<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/demo',
        __DIR__ . '/src',
        __DIR__ . '/src-files',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
     ->withPhpSets(php82: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withSkip([
        FirstClassCallableRector::class
    ])
    ->withSkip([
        __DIR__ . '/src/ResourceObject.php'
    ])
    ->withSkip([
        __DIR__ . '/src/*Interface.php'
    ])
    ->withSkip([
        __DIR__ . '/src/Abstract*.php'
    ]);
