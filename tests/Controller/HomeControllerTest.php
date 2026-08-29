<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\HomeController;
use App\Core\Request;
use App\Core\Seo;
use App\Model\Category;
use App\Model\Post;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;
use App\Tests\Support\SpyView;

final class HomeControllerTest extends DatabaseTestCase
{
    private SpyView $view;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view = new SpyView();
    }

    public function testRendersHomeTemplateWithCanonicalAndDescription(): void
    {
        $this->controller()->index(new Request('/', []));

        self::assertSame('home.tpl', $this->view->template);
        self::assertSame('http://localhost:8080/', $this->view->var('canonical'));
        self::assertNotSame('', $this->view->var('description'));
    }

    public function testSkipsCategoriesWithoutPosts(): void
    {
        $withPosts = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        Fixtures::createCategory($this->pdo, 'Пусто', 'empty');
        $post = Fixtures::createPost($this->pdo, 'Пост', 'post', '2026-01-01 10:00:00');
        Fixtures::attach($this->pdo, $post, $withPosts);

        $this->controller()->index(new Request('/', []));

        /** @var list<Category> $categories */
        $categories = $this->view->var('categories');

        self::assertSame(['php'], array_map(static fn (Category $c): string => $c->slug, $categories));
    }

    public function testGivesAtMostThreeNewestPostsPerCategory(): void
    {
        $category = Fixtures::createCategory($this->pdo, 'PHP', 'php');

        foreach (['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'] as $index => $date) {
            $post = Fixtures::createPost($this->pdo, 'Пост ' . $index, 'post-' . $index, $date . ' 10:00:00');
            Fixtures::attach($this->pdo, $post, $category);
        }

        $this->controller()->index(new Request('/', []));

        /** @var array<int, list<Post>> $byCategory */
        $byCategory = $this->view->var('postsByCategory');
        $titles = array_map(static fn (Post $p): string => $p->title, $byCategory[$category]);

        self::assertSame(['Пост 3', 'Пост 2', 'Пост 1'], $titles);
    }

    private function controller(): HomeController
    {
        return new HomeController(
            new CategoryRepository($this->pdo),
            new PostRepository($this->pdo),
            $this->view,
            new Seo('http://localhost:8080'),
        );
    }
}
