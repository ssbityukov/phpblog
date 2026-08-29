<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\PostRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;

final class PostRepositoryListingTest extends DatabaseTestCase
{
    private int $category;
    private int $old;
    private int $middle;
    private int $fresh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        $this->old = Fixtures::createPost($this->pdo, 'Старый', 'old', '2026-01-01 10:00:00', 500);
        $this->middle = Fixtures::createPost($this->pdo, 'Средний', 'middle', '2026-02-01 10:00:00', 100);
        $this->fresh = Fixtures::createPost($this->pdo, 'Новый', 'fresh', '2026-03-01 10:00:00', 300);

        foreach ([$this->old, $this->middle, $this->fresh] as $post) {
            Fixtures::attach($this->pdo, $post, $this->category);
        }
    }

    public function testCountsPostsInCategory(): void
    {
        self::assertSame(3, (new PostRepository($this->pdo))->countByCategory($this->category));
    }

    public function testSortsByPublishedDateByDefault(): void
    {
        $posts = (new PostRepository($this->pdo))->findByCategory($this->category, 'date', 10, 0);

        self::assertSame([$this->fresh, $this->middle, $this->old], $this->ids($posts));
    }

    public function testSortsByViews(): void
    {
        $posts = (new PostRepository($this->pdo))->findByCategory($this->category, 'views', 10, 0);

        self::assertSame([$this->old, $this->fresh, $this->middle], $this->ids($posts));
    }

    public function testUnknownSortFallsBackToDate(): void
    {
        $posts = (new PostRepository($this->pdo))->findByCategory($this->category, 'p.views; DROP TABLE posts', 10, 0);

        self::assertSame([$this->fresh, $this->middle, $this->old], $this->ids($posts));
    }

    public function testAppliesLimitAndOffset(): void
    {
        $repository = new PostRepository($this->pdo);

        self::assertSame([$this->fresh, $this->middle], $this->ids($repository->findByCategory($this->category, 'date', 2, 0)));
        self::assertSame([$this->old], $this->ids($repository->findByCategory($this->category, 'date', 2, 2)));
    }

    public function testExcludesPostsFromOtherCategories(): void
    {
        $other = Fixtures::createCategory($this->pdo, 'Docker', 'docker');
        $alien = Fixtures::createPost($this->pdo, 'Чужой', 'alien', '2026-04-01 10:00:00');
        Fixtures::attach($this->pdo, $alien, $other);

        $posts = (new PostRepository($this->pdo))->findByCategory($this->category, 'date', 10, 0);

        self::assertNotContains($alien, $this->ids($posts));
    }

    /**
     * @param list<\App\Model\Post> $posts
     * @return list<int>
     */
    private function ids(array $posts): array
    {
        return array_map(static fn ($post) => $post->id, $posts);
    }
}
