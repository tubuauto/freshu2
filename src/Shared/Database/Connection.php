<?php

declare(strict_types=1);

namespace App\Shared\Database;

use App\Shared\Support\Env;
use PDO;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '5432');
        $db = Env::get('DB_NAME', 'fresh2u');
        $schema = Env::get('DB_SCHEMA', 'public');
        $user = Env::get('DB_USER', 'postgres');
        $pass = Env::get('DB_PASS', 'postgres');

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;options=--search_path=%s', $host, $port, $db, $schema);
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
