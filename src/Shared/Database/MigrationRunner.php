<?php

declare(strict_types=1);

namespace App\Shared\Database;

use PDO;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGSERIAL PRIMARY KEY,
                filename TEXT NOT NULL UNIQUE,
                executed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )'
        );
    }

    public function run(string $migrationDir): void
    {
        $this->ensureMigrationsTable();

        $files = glob(rtrim($migrationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql');
        if ($files === false) {
            return;
        }

        sort($files);

        $existing = $this->pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $applied = array_flip($existing);

        foreach ($files as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                continue;
            }

            $this->pdo->beginTransaction();
            try {
                $this->pdo->exec($sql);
                $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');
                $stmt->execute(['filename' => $name]);
                $this->pdo->commit();
                echo "Applied migration: {$name}" . PHP_EOL;
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }
    }
}
