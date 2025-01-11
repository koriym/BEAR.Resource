<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/demo',
        __DIR__ . '/src',
        __DIR__ . '/src-files',
        __DIR__ . '/tests',
        __DIR__ . '/tests-php8',
    ])
    // uncomment to reach your current PHP version
     ->withPhpSets(php81: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withSkip([
        __DIR__ . '/src/ResourceObject.php'
    ])
    ->withSkip([
        __DIR__ . '/src/*Interface.php'
    ])
    ->withSkip([
        __DIR__ . '/src/Abstract*.php'
    ]);
