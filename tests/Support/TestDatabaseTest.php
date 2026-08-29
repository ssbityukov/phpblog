<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TestDatabaseTest extends TestCase
{
    public function testAcceptsDatabaseWithTestSuffix(): void
    {
        $this->expectNotToPerformAssertions();

        TestDatabase::guard('blog_test');
    }

    public function testRejectsProductionLookingDatabase(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('#"blog"#');

        TestDatabase::guard('blog');
    }

    public function testRejectsNameThatMerelyContainsTest(): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabase::guard('test_blog');
    }

    public function testCurrentRunUsesTestDatabase(): void
    {
        self::assertStringEndsWith('_test', TestDatabase::name());
    }
}
