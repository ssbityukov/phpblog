<?php

declare(strict_types=1);

namespace App\Tests\Database;

use App\Database\Seeder;
use PHPUnit\Framework\TestCase;

final class SeederTest extends TestCase
{
    public function testProducesRequestedNumberOfTitles(): void
    {
        self::assertCount(60, Seeder::titles(60));
    }

    public function testAllTitlesAreUnique(): void
    {
        $titles = Seeder::titles(60);

        self::assertSame(60, count(array_unique($titles)), 'Заголовки должны быть уникальными.');
    }

    public function testRefusesToProduceMoreTitlesThanCombinationsExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Seeder::titles(1000);
    }

    public function testUsesInstrumentalCaseAfterPrepositionS(): void
    {
        $titles = Seeder::titles(80);

        self::assertContains('Пять приёмов работы с подготовленными запросами', $titles);
        self::assertContains('Частые ошибки при работе с шаблонизацией', $titles);
    }

    public function testUsesSoBeforeConsonantCluster(): void
    {
        $titles = Seeder::titles(80);

        self::assertContains('Пять приёмов работы со структурой проекта', $titles);
        self::assertContains('Пять приёмов работы со связями многие-ко-многим', $titles);
        self::assertContains('Частые ошибки при работе со сборкой стилей', $titles);
    }

    public function testUsesAccusativeCaseAfterOtherPrefixes(): void
    {
        $titles = Seeder::titles(80);

        self::assertContains('Разбираем индексы в MySQL', $titles);
        self::assertContains('Практическое введение в пагинацию', $titles);
        self::assertContains('Оптимизируем структуру проекта', $titles);
    }

    public function testNeverEmitsNominativeAfterPreposition(): void
    {
        foreach (Seeder::titles(80) as $title) {
            self::assertDoesNotMatchRegularExpression('/ (с|со) [а-яё]+у /u', $title . ' ');
        }
    }
}
