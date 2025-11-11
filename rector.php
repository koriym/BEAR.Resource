<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/demo',
        __DIR__ . '/src',
        __DIR__ . '/src-files',
        __DIR__ . '/src-web-context',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withSets([
        PHPUnitSetList::PHPUNIT_110,
    ])
    ->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(1)
    ->withCodeQualityLevel(1)
    ->withSkip([
        __DIR__ . '/src/ResourceObject.php'
    ])
    ->withSkip([
        __DIR__ . '/src/*Interface.php'
    ])
    ->withSkip([
        __DIR__ . '/src/Abstract*.php'
    ]);

