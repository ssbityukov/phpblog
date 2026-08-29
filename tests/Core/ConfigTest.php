<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testReadsKeyValuePairs(): void
    {
        $config = Config::fromFile($this->writeEnv("DB_HOST=mysql\nDB_PORT=3306\n"));

        self::assertSame('mysql', $config->get('DB_HOST'));
        self::assertSame('3306', $config->get('DB_PORT'));
    }

    public function testIgnoresCommentsAndBlankLines(): void
    {
        $config = Config::fromFile($this->writeEnv("# comment\n\nDB_HOST=mysql\n"));

        self::assertSame('mysql', $config->get('DB_HOST'));
        self::assertNull($config->get('# comment'));
    }

    public function testReturnsDefaultForMissingKey(): void
    {
        $config = Config::fromFile($this->writeEnv("DB_HOST=mysql\n"));

        self::assertSame('fallback', $config->get('DB_NAME', 'fallback'));
    }

    public function testValueMayContainEqualsSign(): void
    {
        $config = Config::fromFile($this->writeEnv("DB_PASSWORD=pa=ss\n"));

        self::assertSame('pa=ss', $config->get('DB_PASSWORD'));
    }

    private function writeEnv(string $contents): string
    {
        $path = sys_get_temp_dir() . '/env_' . uniqid('', true);
        file_put_contents($path, $contents);

        return $path;
    }
}
