<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Post;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    public function testFormatsPublishedDate(): void
    {
        self::assertSame('01.05.2026', $this->post()->publishedDate());
    }

    public function testBodyHtmlTurnsNewlinesIntoLineBreaks(): void
    {
        $post = $this->post("Первая строка\nВторая строка");

        self::assertSame('Первая строка<br />' . "\n" . 'Вторая строка', $post->bodyHtml());
    }

    public function testBodyHtmlEscapesMarkup(): void
    {
        $post = $this->post('<script>alert(1)</script>');

        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $post->bodyHtml());
    }

    public function testBodyHtmlEscapesAmpersandsAndQuotes(): void
    {
        $post = $this->post('Кофе & чай, "кавычки", \'апострофы\'');

        self::assertSame(
            'Кофе &amp; чай, &quot;кавычки&quot;, &#039;апострофы&#039;',
            $post->bodyHtml(),
        );
    }

    public function testBodyHtmlKeepsCyrillicIntact(): void
    {
        self::assertSame('Привет, мир', $this->post('Привет, мир')->bodyHtml());
    }

    public function testBodyHtmlEscapesBeforeInsertingBreaks(): void
    {
        $post = $this->post("<img src=x\nonerror=alert(1)>");

        self::assertStringNotContainsString('<img', $post->bodyHtml());
        self::assertStringContainsString('<br />', $post->bodyHtml());
    }

    private function post(string $body = 'Текст'): Post
    {
        return new Post(1, 'slug', 'Заголовок', 'Анонс', $body, null, 0, '2026-05-01 10:00:00');
    }
}
