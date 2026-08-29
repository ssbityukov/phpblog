<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\PostController;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Seo;
use App\Model\Category;
use App\Model\Post;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;
use App\Tests\Support\SpyView;

final class PostControllerTest extends DatabaseTestCase
{
    private SpyView $view;
    private int $category;
    private int $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view = new SpyView();
        $this->category = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        $this->post = Fixtures::createPost($this->pdo, 'Мой пост', 'my-post', '2026-01-10 10:00:00', 5);
        Fixtures::attach($this->pdo, $this->post, $this->category);
    }

    public function testUnknownSlugIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->controller()->show(new Request('/post/nope', []), 'nope');
    }

    public function testRendersPostTemplateWithSeoFieldsAndCategories(): void
    {
        $this->controller()->show(new Request('/post/my-post', []), 'my-post');

        self::assertSame('post.tpl', $this->view->template);
        self::assertSame('Мой пост', $this->view->var('title'));
        self::assertSame('Мой пост description', $this->view->var('description'));
        self::assertSame('http://localhost:8080/post/my-post', $this->view->var('canonical'));

        /** @var list<Category> $categories */
        $categories = $this->view->var('categories');
        self::assertSame(['php'], array_map(static fn (Category $c): string => $c->slug, $categories));
    }

    public function testGetIncrementsViewsAndShowsTheIncrementedNumber(): void
    {
        $this->controller()->show(new Request('/post/my-post', []), 'my-post');

        self::assertSame(6, $this->storedViews());
        self::assertSame(6, $this->view->var('views'));
    }

    public function testHeadRequestDoesNotIncrementViews(): void
    {
        $this->controller()->show(new Request('/post/my-post', [], 'HEAD'), 'my-post');

        self::assertSame(5, $this->storedViews());
    }

    public function testSimilarPostsShareCategoryAndExcludeThePostItself(): void
    {
        $sibling = Fixtures::createPost($this->pdo, 'Сосед', 'sibling', '2026-01-09 10:00:00');
        Fixtures::attach($this->pdo, $sibling, $this->category);

        $other = Fixtures::createCategory($this->pdo, 'MySQL', 'mysql');
        Fixtures::attach($this->pdo, Fixtures::createPost($this->pdo, 'Чужой', 'alien', '2026-01-09 10:00:00'), $other);

        $this->controller()->show(new Request('/post/my-post', []), 'my-post');

        self::assertSame(['Сосед'], $this->similarTitles());
    }

    public function testSimilarPostsAreLimitedToThree(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $sibling = Fixtures::createPost($this->pdo, 'Сосед ' . $i, 'sibling-' . $i, sprintf('2026-01-%02d 10:00:00', $i));
            Fixtures::attach($this->pdo, $sibling, $this->category);
        }

        $this->controller()->show(new Request('/post/my-post', []), 'my-post');

        self::assertCount(3, $this->similarTitles());
    }

    private function storedViews(): int
    {
        $statement = $this->pdo->prepare('SELECT views FROM posts WHERE id = :id');
        $statement->execute(['id' => $this->post]);

        return (int) $statement->fetchColumn();
    }

    /** @return list<string> */
    private function similarTitles(): array
    {
        /** @var list<Post> $similar */
        $similar = $this->view->var('similar');

        return array_map(static fn (Post $post): string => $post->title, $similar);
    }

    private function controller(): PostController
    {
        return new PostController(
            new CategoryRepository($this->pdo),
            new PostRepository($this->pdo),
            $this->view,
            new Seo('http://localhost:8080'),
        );
    }
}
