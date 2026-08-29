<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\CategoryController;
use App\Core\NotFoundException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Seo;
use App\Model\Post;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;
use App\Tests\Support\SpyView;

final class CategoryControllerTest extends DatabaseTestCase
{
    private SpyView $view;
    private int $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view = new SpyView();
        $this->category = Fixtures::createCategory($this->pdo, 'PHP', 'php');
    }

    public function testUnknownSlugIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->controller()->show(new Request('/category/nope', []), 'nope');
    }

    public function testRendersCategoryTemplateWithSeoFields(): void
    {
        $this->controller()->show(new Request('/category/php', []), 'php');

        self::assertSame('category.tpl', $this->view->template);
        self::assertSame('PHP', $this->view->var('title'));
        self::assertSame('PHP description', $this->view->var('description'));
        self::assertSame('http://localhost:8080/category/php', $this->view->var('canonical'));
    }

    public function testKeepsKnownSortAndFallsBackToDateOnGarbage(): void
    {
        $this->controller()->show(new Request('/category/php?sort=views', ['sort' => 'views']), 'php');
        self::assertSame('views', $this->view->var('sort'));

        $this->controller()->show(new Request('/category/php?sort=hack', ['sort' => 'hack']), 'php');
        self::assertSame('date', $this->view->var('sort'));
    }

    public function testSortByViewsChangesPostOrder(): void
    {
        $this->attach(Fixtures::createPost($this->pdo, 'Свежий', 'fresh', '2026-01-02 10:00:00', 1));
        $this->attach(Fixtures::createPost($this->pdo, 'Популярный', 'popular', '2026-01-01 10:00:00', 99));

        $this->controller()->show(new Request('/category/php?sort=views', ['sort' => 'views']), 'php');

        self::assertSame(['Популярный', 'Свежий'], $this->titles());
    }

    public function testSecondPageUsesOffsetAndCanonicalWithPage(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->attach(Fixtures::createPost(
                $this->pdo,
                sprintf('Пост %02d', $i),
                'post-' . $i,
                sprintf('2026-01-%02d 10:00:00', $i),
            ));
        }

        $this->controller()->show(new Request('/category/php?page=2', ['page' => '2']), 'php');

        /** @var Paginator $paginator */
        $paginator = $this->view->var('paginator');

        self::assertSame(2, $paginator->page());
        self::assertSame(12, $paginator->total());
        self::assertSame(10, $paginator->offset());
        self::assertSame(['Пост 02', 'Пост 01'], $this->titles());
        self::assertSame('http://localhost:8080/category/php?page=2', $this->view->var('canonical'));
    }

    public function testPostsOfOtherCategoriesAreNotListed(): void
    {
        $other = Fixtures::createCategory($this->pdo, 'MySQL', 'mysql');
        Fixtures::attach($this->pdo, Fixtures::createPost($this->pdo, 'Чужой', 'alien', '2026-01-01 10:00:00'), $other);
        $this->attach(Fixtures::createPost($this->pdo, 'Свой', 'own', '2026-01-01 10:00:00'));

        $this->controller()->show(new Request('/category/php', []), 'php');

        self::assertSame(['Свой'], $this->titles());
    }

    private function attach(int $postId): void
    {
        Fixtures::attach($this->pdo, $postId, $this->category);
    }

    /** @return list<string> */
    private function titles(): array
    {
        /** @var list<Post> $posts */
        $posts = $this->view->var('posts');

        return array_map(static fn (Post $post): string => $post->title, $posts);
    }

    private function controller(): CategoryController
    {
        return new CategoryController(
            new CategoryRepository($this->pdo),
            new PostRepository($this->pdo),
            $this->view,
            new Seo('http://localhost:8080'),
        );
    }
}
