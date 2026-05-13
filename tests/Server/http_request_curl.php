<?php

declare(strict_types=1);

$case = (string) ($_GET['case'] ?? 'valid');

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

if ($case === 'empty') {
    exit(0);
}

if ($case === 'invalid') {
    echo '{';
    exit(0);
}

echo '{"ok":true}';
