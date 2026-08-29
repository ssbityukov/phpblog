<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\Seo;
use PHPUnit\Framework\TestCase;

final class SeoTest extends TestCase
{
    public function testBuildsAbsoluteCanonicalUrl(): void
    {
        self::assertSame('https://blog.test/post/hello', $this->seo()->canonical('/post/hello'));
    }

    public function testStripsTrailingSlashFromBaseUrl(): void
    {
        $seo = new Seo('https://blog.test/');

        self::assertSame('https://blog.test/post/hello', $seo->canonical('/post/hello'));
    }

    public function testKeepsRootPath(): void
    {
        self::assertSame('https://blog.test/', $this->seo()->canonical('/'));
    }

    public function testAppendsPageParameterOnlyBeyondFirstPage(): void
    {
        self::assertSame('https://blog.test/category/php', $this->seo()->canonical('/category/php', 1));
        self::assertSame('https://blog.test/category/php?page=3', $this->seo()->canonical('/category/php', 3));
    }

    public function testCollapsesWhitespaceInDescription(): void
    {
        self::assertSame('Первая строка Вторая', $this->seo()->description("Первая строка\n\n  Вторая"));
    }

    public function testStripsTagsFromDescription(): void
    {
        self::assertSame('Жирный текст', $this->seo()->description('<b>Жирный</b> текст'));
    }

    public function testTruncatesOnWordBoundary(): void
    {
        $description = $this->seo()->description('один два три четыре пять', 12);

        self::assertSame('один два…', $description);
    }

    public function testKeepsShortDescriptionUntouched(): void
    {
        self::assertSame('Короткий анонс', $this->seo()->description('Короткий анонс', 160));
    }

    public function testCountsMultibyteCharactersNotBytes(): void
    {
        // 20 кириллических символов — это 40 байт; обрезка по байтам порвала бы символ.
        $text = str_repeat('я', 20);

        self::assertSame($text, $this->seo()->description($text, 20));
    }

    private function seo(): Seo
    {
        return new Seo('https://blog.test');
    }
}
