<?php

declare(strict_types=1);

namespace App\Tests\View;

use App\Tests\Support\TemplateTestCase;

final class HomeTemplateTest extends TemplateTestCase
{
    public function testRendersCategoryBlockWithItsPosts(): void
    {
        $category = self::category();

        $html = $this->view->render('home.tpl', [
            'title' => '',
            'description' => 'Описание',
            'canonical' => 'http://localhost:8080/',
            'categories' => [$category],
            'postsByCategory' => [$category->id => [self::post('Первый', 'first'), self::post('Второй', 'second')]],
        ]);

        self::assertStringContainsString('<a href="/category/php">PHP</a>', $html);
        self::assertStringContainsString('<a href="/post/first">Первый</a>', $html);
        self::assertStringContainsString('<a href="/post/second">Второй</a>', $html);
    }

    public function testShowsSeedHintWhenThereAreNoCategories(): void
    {
        $html = $this->view->render('home.tpl', [
            'title' => '',
            'description' => '',
            'canonical' => '',
            'categories' => [],
            'postsByCategory' => [],
        ]);

        self::assertStringContainsString('php bin/console seed', $html);
        self::assertStringNotContainsString('class="card"', $html);
    }

    public function testPostCardShowsImageOnlyWhenPostHasOne(): void
    {
        $category = self::category();

        $withImage = $this->view->render('home.tpl', [
            'title' => '', 'description' => '', 'canonical' => '',
            'categories' => [$category],
            'postsByCategory' => [$category->id => [self::post(image: 'cover.webp')]],
        ]);
        $withoutImage = $this->view->render('home.tpl', [
            'title' => '', 'description' => '', 'canonical' => '',
            'categories' => [$category],
            'postsByCategory' => [$category->id => [self::post()]],
        ]);

        self::assertStringContainsString('src="/uploads/posts/cover.webp"', $withImage);
        self::assertStringNotContainsString('card__image', $withoutImage);
    }

    public function testPostCardShowsDateAndViews(): void
    {
        $category = self::category();

        $html = $this->view->render('home.tpl', [
            'title' => '', 'description' => '', 'canonical' => '',
            'categories' => [$category],
            'postsByCategory' => [$category->id => [self::post(views: 42)]],
        ]);

        self::assertStringContainsString('10.01.2026', $html);
        self::assertStringContainsString('42 просмотров', $html);
    }
}
