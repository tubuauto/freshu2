<?php

declare(strict_types=1);

use App\Shared\Support\Env;

return [
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => (int) Env::get('DB_PORT', '5432'),
    'database' => Env::get('DB_NAME', 'fresh2u'),
    'user' => Env::get('DB_USER', 'postgres'),
    'password' => Env::get('DB_PASS', 'postgres'),
    'schema' => Env::get('DB_SCHEMA', 'public'),
];
