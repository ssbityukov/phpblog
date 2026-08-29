<?php

declare(strict_types=1);

namespace App\Tests\Database;

use App\Database\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Мигратор делает DDL, а DDL в MySQL не откатывается транзакцией. Поэтому тесты
 * работают в собственной схеме, которая создаётся и сносится на каждый тест, —
 * иначе они бы снесли схему остальной сюиты.
 */
final class MigratorTest extends TestCase
{
    private const DATABASE = 'blog_migrator_test';

    private PDO $pdo;
    private string $dir;

    protected function setUp(): void
    {
        $server = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', self::env('DB_HOST', '127.0.0.1'), self::env('DB_PORT', '3306')),
            self::env('DB_USERNAME', 'root'),
            self::env('DB_PASSWORD', ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $server->exec('DROP DATABASE IF EXISTS ' . self::DATABASE);
        $server->exec('CREATE DATABASE ' . self::DATABASE . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                self::env('DB_HOST', '127.0.0.1'),
                self::env('DB_PORT', '3306'),
                self::DATABASE,
            ),
            self::env('DB_USERNAME', 'root'),
            self::env('DB_PASSWORD', ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        $this->dir = sys_get_temp_dir() . '/migrator-test-' . bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP DATABASE IF EXISTS ' . self::DATABASE);

        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->dir);
    }

    public function testAppliesMigrationsInFilenameOrder(): void
    {
        $this->write('002_second.sql', 'CREATE TABLE second (id INT PRIMARY KEY)');
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');

        $applied = $this->migrator()->migrate();

        self::assertSame(['001_first.sql', '002_second.sql'], $applied);
        self::assertSame(['001_first.sql', '002_second.sql'], $this->recorded());
        self::assertContains('first', $this->tables());
        self::assertContains('second', $this->tables());
    }

    public function testSecondRunAppliesNothing(): void
    {
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');
        $this->migrator()->migrate();

        self::assertSame([], $this->migrator()->migrate());
        self::assertSame(['001_first.sql'], $this->recorded());
    }

    public function testAppliesOnlyNewFile(): void
    {
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');
        $this->migrator()->migrate();

        $this->write('002_second.sql', 'CREATE TABLE second (id INT PRIMARY KEY)');
        $migrator = $this->migrator();

        self::assertSame(['002_second.sql'], $migrator->pending());
        self::assertSame(['002_second.sql'], $migrator->migrate());
    }

    public function testFreshDropsExistingTablesAndReappliesEverything(): void
    {
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');
        $this->migrator()->migrate();
        $this->pdo->exec('INSERT INTO first (id) VALUES (1)');

        $applied = $this->migrator()->fresh();

        self::assertSame(['001_first.sql'], $applied);
        self::assertSame(['001_first.sql'], $this->recorded());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM first')->fetchColumn());
    }

    public function testFreshDropsTablesThatNoMigrationOwns(): void
    {
        $this->pdo->exec('CREATE TABLE leftover (id INT PRIMARY KEY)');
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');

        $this->migrator()->fresh();

        self::assertNotContains('leftover', $this->tables());
    }

    public function testFreshRespectsForeignKeys(): void
    {
        $this->write('001_parent.sql', 'CREATE TABLE parent (id INT UNSIGNED PRIMARY KEY) ENGINE = InnoDB');
        $this->write(
            '002_child.sql',
            'CREATE TABLE child (
                id INT UNSIGNED PRIMARY KEY,
                parent_id INT UNSIGNED NOT NULL,
                CONSTRAINT child_parent_fk FOREIGN KEY (parent_id) REFERENCES parent (id)
            ) ENGINE = InnoDB'
        );
        $this->migrator()->migrate();

        self::assertSame(['001_parent.sql', '002_child.sql'], $this->migrator()->fresh());
    }

    public function testStatusReportsAppliedPendingAndMissing(): void
    {
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');
        $this->migrator()->migrate();
        $this->write('002_second.sql', 'CREATE TABLE second (id INT PRIMARY KEY)');
        $this->pdo->exec("INSERT INTO migrations (migration) VALUES ('000_gone.sql')");

        $status = $this->migrator()->status();

        self::assertSame('001_first.sql', $status[0]['migration']);
        self::assertNotNull($status[0]['applied_at']);
        self::assertFalse($status[0]['missing']);

        self::assertSame('002_second.sql', $status[1]['migration']);
        self::assertNull($status[1]['applied_at']);

        self::assertSame('000_gone.sql', $status[2]['migration']);
        self::assertTrue($status[2]['missing']);
    }

    public function testBrokenMigrationFailsWithItsNameAndKeepsEarlierOnes(): void
    {
        $this->write('001_first.sql', 'CREATE TABLE first (id INT PRIMARY KEY)');
        $this->write('002_broken.sql', 'CREATE TABLE ((( broken');

        try {
            $this->migrator()->migrate();
            self::fail('Битая миграция должна бросать исключение.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('002_broken.sql', $error->getMessage());
        }

        self::assertSame(['001_first.sql'], $this->recorded());
        self::assertSame(['002_broken.sql'], $this->migrator()->pending());
    }

    public function testRejectsMissingMigrationsDirectory(): void
    {
        $this->expectException(RuntimeException::class);

        (new Migrator($this->pdo, $this->dir . '/nope'))->migrate();
    }

    public function testProjectMigrationsBuildTheSchemaTheAppExpects(): void
    {
        $migrator = new Migrator($this->pdo, dirname(__DIR__, 2) . '/database/migrations');
        $migrator->migrate();

        self::assertSame(['categories', 'migrations', 'post_category', 'posts'], $this->tables());
        self::assertSame([], $migrator->pending());
    }

    private function migrator(): Migrator
    {
        return new Migrator($this->pdo, $this->dir);
    }

    private function write(string $name, string $sql): void
    {
        file_put_contents($this->dir . '/' . $name, $sql . ';');
    }

    /** @return list<string> */
    private function recorded(): array
    {
        /** @var list<string> $rows */
        $rows = $this->pdo->query('SELECT migration FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

        return $rows;
    }

    /** @return list<string> */
    private function tables(): array
    {
        /** @var list<string> $tables */
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        sort($tables);

        return $tables;
    }

    private static function env(string $key, string $default): string
    {
        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
