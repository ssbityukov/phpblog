<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /** @var list<string> */
    private array $putenv = [];

    protected function tearDown(): void
    {
        foreach ($this->putenv as $key) {
            putenv($key);
        }

        $this->putenv = [];
    }

    public function testReadsKeyValuePairs(): void
    {
        $config = Config::load($this->writeEnv("CFG_HOST=mysql\nCFG_PORT=3306\n"));

        self::assertSame('mysql', $config->get('CFG_HOST'));
        self::assertSame('3306', $config->get('CFG_PORT'));
    }

    public function testIgnoresCommentsAndBlankLines(): void
    {
        $config = Config::load($this->writeEnv("# comment\n\nCFG_HOST=mysql\n"));

        self::assertSame('mysql', $config->get('CFG_HOST'));
        self::assertNull($config->get('# comment'));
    }

    public function testReturnsDefaultForMissingKey(): void
    {
        $config = Config::load($this->writeEnv("CFG_HOST=mysql\n"));

        self::assertSame('fallback', $config->get('CFG_NAME', 'fallback'));
    }

    public function testValueMayContainEqualsSign(): void
    {
        $config = Config::load($this->writeEnv("CFG_PASSWORD=pa=ss\n"));

        self::assertSame('pa=ss', $config->get('CFG_PASSWORD'));
    }

    public function testEnvironmentVariableWinsOverFile(): void
    {
        $this->setEnv('CFG_HOST', 'from-env');

        $config = Config::load($this->writeEnv("CFG_HOST=from-file\n"));

        self::assertSame('from-env', $config->get('CFG_HOST'));
    }

    public function testReadsEnvironmentVariableWhenFileIsMissing(): void
    {
        $this->setEnv('CFG_HOST', 'from-env');

        $config = Config::load(sys_get_temp_dir() . '/env_absent_' . uniqid('', true));

        self::assertSame('from-env', $config->get('CFG_HOST'));
    }

    public function testMissingFileIsNotAnError(): void
    {
        $config = Config::load(sys_get_temp_dir() . '/env_absent_' . uniqid('', true));

        self::assertSame('fallback', $config->get('CFG_HOST', 'fallback'));
    }

    public function testEmptyEnvironmentVariableDoesNotOverrideFile(): void
    {
        $this->setEnv('CFG_HOST', '');

        $config = Config::load($this->writeEnv("CFG_HOST=from-file\n"));

        self::assertSame('from-file', $config->get('CFG_HOST'));
    }

    public function testStripsSurroundingQuotes(): void
    {
        $config = Config::load($this->writeEnv("CFG_PASSWORD=\"pa ss\"\nCFG_URL='http://localhost'\n"));

        self::assertSame('pa ss', $config->get('CFG_PASSWORD'));
        self::assertSame('http://localhost', $config->get('CFG_URL'));
    }

    public function testStripsInlineComment(): void
    {
        $config = Config::load($this->writeEnv("CFG_HOST=mysql # local\n"));

        self::assertSame('mysql', $config->get('CFG_HOST'));
    }

    public function testKeepsHashInsideQuotedValue(): void
    {
        $config = Config::load($this->writeEnv("CFG_PASSWORD=\"pa#ss\"\n"));

        self::assertSame('pa#ss', $config->get('CFG_PASSWORD'));
    }

    public function testKeepsHashThatIsNotPrecededByWhitespace(): void
    {
        $config = Config::load($this->writeEnv("CFG_PASSWORD=pa#ss\n"));

        self::assertSame('pa#ss', $config->get('CFG_PASSWORD'));
    }

    public function testBoolIsTrueOnlyForAffirmativeValues(): void
    {
        $config = Config::load($this->writeEnv(
            "A=1\nB=true\nC=TRUE\nD=on\nE=yes\nF=0\nG=false\nH=\nI=maybe\n"
        ));

        foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
            self::assertTrue($config->bool($key), $key . ' should be true');
        }

        foreach (['F', 'G', 'H', 'I'] as $key) {
            self::assertFalse($config->bool($key), $key . ' should be false');
        }
    }

    public function testBoolDefaultsToFalseForMissingKey(): void
    {
        $config = Config::load($this->writeEnv("CFG_HOST=mysql\n"));

        self::assertFalse($config->bool('CFG_DEBUG'));
    }

    private function setEnv(string $key, string $value): void
    {
        putenv($key . '=' . $value);
        $this->putenv[] = $key;
    }

    private function writeEnv(string $contents): string
    {
        $path = sys_get_temp_dir() . '/env_' . uniqid('', true);
        file_put_contents($path, $contents);

        return $path;
    }
}
