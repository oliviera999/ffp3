<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\TextUI\Application;

$args = $_SERVER['argv'] ?? [];
$filtered = [];

foreach ($args as $arg) {
    if (strtolower(basename($arg)) === 'desktop.ini') {
        continue;
    }
    $filtered[] = $arg;
}

if (!in_array('--configuration', $filtered, true) && !in_array('-c', $filtered, true)) {
    $filtered[] = '--configuration';
    $filtered[] = __DIR__ . '/../phpunit.xml';
}

exit((new Application())->run($filtered));
