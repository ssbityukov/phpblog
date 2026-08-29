<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\PostRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;

final class PostRepositoryLatestTest extends DatabaseTestCase
{
    public function testReturnsAtMostThreeNewestPostsPerCategory(): void
    {
        $php = Fixtures::createCategory($this->pdo, 'PHP', 'php');

        $oldest = Fixtures::createPost($this->pdo, 'Самый старый', 'p1', '2026-01-01 10:00:00');
        $second = Fixtures::createPost($this->pdo, 'Второй', 'p2', '2026-02-01 10:00:00');
        $third = Fixtures::createPost($this->pdo, 'Третий', 'p3', '2026-03-01 10:00:00');
        $newest = Fixtures::createPost($this->pdo, 'Новый', 'p4', '2026-04-01 10:00:00');

        foreach ([$oldest, $second, $third, $newest] as $post) {
            Fixtures::attach($this->pdo, $post, $php);
        }

        $result = (new PostRepository($this->pdo))->latestByCategories(3);

        self::assertSame(
            [$newest, $third, $second],
            array_map(static fn ($post) => $post->id, $result[$php]),
        );
    }

    public function testGroupsPostsByCategory(): void
    {
        $php = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        $sql = Fixtures::createCategory($this->pdo, 'SQL', 'sql');

        $shared = Fixtures::createPost($this->pdo, 'Общий', 'shared', '2026-03-01 10:00:00');
        $onlySql = Fixtures::createPost($this->pdo, 'Только SQL', 'only-sql', '2026-02-01 10:00:00');

        Fixtures::attach($this->pdo, $shared, $php);
        Fixtures::attach($this->pdo, $shared, $sql);
        Fixtures::attach($this->pdo, $onlySql, $sql);

        $result = (new PostRepository($this->pdo))->latestByCategories(3);

        self::assertSame([$shared], array_map(static fn ($post) => $post->id, $result[$php]));
        self::assertSame([$shared, $onlySql], array_map(static fn ($post) => $post->id, $result[$sql]));
    }

    public function testReturnsEmptyArrayWhenNoPosts(): void
    {
        self::assertSame([], (new PostRepository($this->pdo))->latestByCategories(3));
    }
}
