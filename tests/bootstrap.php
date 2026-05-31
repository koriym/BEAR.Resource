<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$removeFiles = static function (string $pattern, int $flags = 0): void {
    foreach ((array) glob($pattern, $flags) as $file) {
        if (! is_file($file)) {
            continue;
        }

        unlink($file);
    }
};

$removeFiles(__DIR__ . '/tmp/*.php');
$removeFiles(__DIR__ . '/Module/tmp/{*.txt,*.php}', GLOB_BRACE);
