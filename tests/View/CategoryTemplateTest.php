<?php

declare(strict_types=1);

namespace App\Tests\View;

use App\Core\Paginator;
use App\Tests\Support\TemplateTestCase;

final class CategoryTemplateTest extends TemplateTestCase
{
    public function testMarksActiveSortLink(): void
    {
        $html = $this->render(sort: 'views');

        self::assertMatchesRegularExpression(
            '#sort__link sort__link--active"\s+href="/category/php\?sort=views"#',
            $html,
        );
        self::assertMatchesRegularExpression(
            '#sort__link"\s+href="/category/php\?sort=date"#',
            $html,
        );
    }

    public function testShowsCategoryNameAndDescription(): void
    {
        $html = $this->render();

        self::assertStringContainsString('<h1 class="page-title">PHP</h1>', $html);
        self::assertStringContainsString('PHP description', $html);
    }

    public function testShowsEmptyMessageWithoutPosts(): void
    {
        $html = $this->render(posts: []);

        self::assertStringContainsString('В этой категории пока нет статей.', $html);
    }

    public function testHidesPaginationOnSinglePage(): void
    {
        $html = $this->render(paginator: new Paginator(5, 10, 1));

        self::assertStringNotContainsString('class="pagination"', $html);
    }

    public function testPaginationKeepsSortInLinks(): void
    {
        $html = $this->render(sort: 'views', paginator: new Paginator(30, 10, 2));

        self::assertStringContainsString('href="/category/php?sort=views&page=1">Назад</a>', $html);
        self::assertStringContainsString('href="/category/php?sort=views&page=3">Вперёд</a>', $html);
        self::assertStringContainsString('pagination__link--current">2</span>', $html);
    }

    /** @param list<\App\Model\Post>|null $posts */
    private function render(string $sort = 'date', ?array $posts = null, ?Paginator $paginator = null): string
    {
        return $this->view->render('category.tpl', [
            'title' => 'PHP',
            'description' => 'PHP description',
            'canonical' => 'http://localhost:8080/category/php',
            'category' => self::category(),
            'sort' => $sort,
            'paginator' => $paginator ?? new Paginator(1, 10, 1),
            'posts' => $posts ?? [self::post()],
        ]);
    }
}
