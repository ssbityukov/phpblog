<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    private static ?PDO $connection = null;

    protected PDO $pdo;

    /** Схему накатывает tests/bootstrap.php — здесь только соединение. */
    public static function connect(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            self::env('DB_HOST', '127.0.0.1'),
            self::env('DB_PORT', '3306'),
            self::env('DB_DATABASE', 'blog_test'),
        );

        return self::$connection = new PDO($dsn, self::env('DB_USERNAME', 'root'), self::env('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    protected function setUp(): void
    {
        $this->pdo = self::connect();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    private static function env(string $key, string $default): string
    {
        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
