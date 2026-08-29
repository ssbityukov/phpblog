<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected static PDO $connection;
    protected PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: '127.0.0.1',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_DATABASE') ?: 'blog_test',
        );

        self::$connection = new PDO($dsn, getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        self::$connection->exec((string) file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql'));
    }

    protected function setUp(): void
    {
        $this->pdo = self::$connection;
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }
}
