<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Shared\Database\Connection;

$pdo = Connection::get();
$files = glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . '*.sql');
if ($files === false) {
    echo 'No seed files found.' . PHP_EOL;
    exit(0);
}

sort($files);

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        continue;
    }
    $pdo->exec($sql);
    echo 'Applied seed: ' . basename($file) . PHP_EOL;
}

echo 'Seeding completed.' . PHP_EOL;
