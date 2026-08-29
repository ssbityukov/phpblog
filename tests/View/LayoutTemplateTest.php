<?php

declare(strict_types=1);

namespace App\Tests\View;

use App\Tests\Support\TemplateTestCase;

final class LayoutTemplateTest extends TemplateTestCase
{
    public function testPutsTitleBeforeSiteName(): void
    {
        $html = $this->view->render('404.tpl', ['title' => 'Страница не найдена']);

        self::assertStringContainsString('<title>Страница не найдена — Блог</title>', $html);
    }

    public function testFallsBackToSiteNameWithoutTitle(): void
    {
        $html = $this->view->render('404.tpl', ['title' => '']);

        self::assertStringContainsString('<title>Блог</title>', $html);
    }

    public function testRendersDescriptionAndCanonicalWhenGiven(): void
    {
        $html = $this->view->render('404.tpl', [
            'title' => 'T',
            'description' => 'Описание страницы',
            'canonical' => 'http://localhost:8080/post/my-post',
        ]);

        self::assertStringContainsString('<meta name="description" content="Описание страницы">', $html);
        self::assertStringContainsString('<link rel="canonical" href="http://localhost:8080/post/my-post">', $html);
    }

    public function testOmitsDescriptionAndCanonicalWhenEmpty(): void
    {
        $html = $this->view->render('404.tpl', ['title' => 'T', 'description' => '', 'canonical' => '']);

        self::assertStringNotContainsString('name="description"', $html);
        self::assertStringNotContainsString('rel="canonical"', $html);
    }

    public function testEscapesTitle(): void
    {
        $html = $this->view->render('404.tpl', ['title' => '<script>alert(1)</script>']);

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }
}
