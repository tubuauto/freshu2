<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Shared\Database\Connection;
use App\Shared\Database\MigrationRunner;

$runner = new MigrationRunner(Connection::get());
$runner->run(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');

echo 'Migrations completed.' . PHP_EOL;
