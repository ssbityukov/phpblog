<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Forward-only миграции: каждый .sql-файл применяется один раз, факт применения
 * пишется в таблицу `migrations`.
 *
 * Транзакций тут нет намеренно: DDL в MySQL делает implicit commit, откатить
 * половину применённого файла всё равно нельзя. При ошибке миграция
 * останавливается на упавшем файле, а всё, что применилось до него, остаётся
 * записанным.
 */
final class Migrator
{
    private bool $tableEnsured = false;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsDir,
    ) {
    }

    /** @return list<string> Имена файлов, которые ещё не применялись. */
    public function pending(): array
    {
        $applied = $this->applied();

        return array_values(array_filter(
            $this->files(),
            static fn (string $file): bool => !in_array($file, $applied, true),
        ));
    }

    /** @return list<string> Имена применённых на этом вызове миграций. */
    public function migrate(): array
    {
        $applied = [];

        foreach ($this->pending() as $file) {
            $this->apply($file);
            $applied[] = $file;
        }

        return $applied;
    }

    /**
     * Сносит все таблицы текущей схемы и накатывает миграции с нуля.
     *
     * @return list<string>
     */
    public function fresh(): array
    {
        $this->dropAllTables();
        $this->tableEnsured = false;

        return $this->migrate();
    }

    /** @return list<array{migration: string, applied_at: ?string, missing: bool}> */
    public function status(): array
    {
        $this->ensureTable();

        /** @var array<string, string> $applied */
        $applied = $this->pdo
            ->query('SELECT migration, applied_at FROM migrations ORDER BY migration')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $files = $this->files();
        $status = [];

        foreach ($files as $file) {
            $status[] = [
                'migration' => $file,
                'applied_at' => $applied[$file] ?? null,
                'missing' => false,
            ];
        }

        foreach ($applied as $file => $appliedAt) {
            if (!in_array($file, $files, true)) {
                $status[] = ['migration' => $file, 'applied_at' => $appliedAt, 'missing' => true];
            }
        }

        return $status;
    }

    /** @return list<string> */
    private function files(): array
    {
        if (!is_dir($this->migrationsDir)) {
            throw new RuntimeException(sprintf('Migrations directory "%s" does not exist.', $this->migrationsDir));
        }

        $files = array_map('basename', glob($this->migrationsDir . '/*.sql') ?: []);
        sort($files, SORT_STRING);

        return array_values($files);
    }

    /** @return list<string> */
    private function applied(): array
    {
        $this->ensureTable();

        /** @var list<string> $applied */
        $applied = $this->pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

        return $applied;
    }

    private function apply(string $file): void
    {
        try {
            $this->pdo->exec((string) file_get_contents($this->migrationsDir . '/' . $file));
        } catch (PDOException $error) {
            throw new RuntimeException(
                sprintf('Migration "%s" failed: %s', $file, $error->getMessage()),
                0,
                $error,
            );
        }

        $statement = $this->pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $statement->execute(['migration' => $file]);
    }

    private function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration  VARCHAR(255) NOT NULL,
                applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY migrations_migration_unique (migration)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci'
        );

        $this->tableEnsured = true;
    }

    private function dropAllTables(): void
    {
        /** @var list<string> $tables */
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        if ($tables === []) {
            return;
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $this->pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
