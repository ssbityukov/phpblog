<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\CategoryRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;

final class CategoryRepositoryTest extends DatabaseTestCase
{
    public function testFindAllWithPostsSkipsEmptyCategories(): void
    {
        $filled = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        Fixtures::createCategory($this->pdo, 'Пустая', 'empty');
        $post = Fixtures::createPost($this->pdo, 'Пост', 'post', '2026-01-01 10:00:00');
        Fixtures::attach($this->pdo, $post, $filled);

        $categories = (new CategoryRepository($this->pdo))->findAllWithPosts();

        self::assertCount(1, $categories);
        self::assertSame($filled, $categories[0]->id);
        self::assertSame('PHP', $categories[0]->name);
    }

    public function testFindBySlugReturnsCategory(): void
    {
        $id = Fixtures::createCategory($this->pdo, 'MySQL', 'mysql');

        $category = (new CategoryRepository($this->pdo))->findBySlug('mysql');

        self::assertNotNull($category);
        self::assertSame($id, $category->id);
        self::assertSame('MySQL description', $category->description);
    }

    public function testFindBySlugReturnsNullForUnknownSlug(): void
    {
        self::assertNull((new CategoryRepository($this->pdo))->findBySlug('nope'));
    }

    public function testSlugWithSqlPayloadFindsNothingAndLeavesTableIntact(): void
    {
        Fixtures::createCategory($this->pdo, 'PHP', 'php');

        $category = (new CategoryRepository($this->pdo))->findBySlug("' OR 1=1 -- ");

        self::assertNull($category);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn());
    }

    public function testFindByPostIdReturnsAttachedCategories(): void
    {
        $php = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        $sql = Fixtures::createCategory($this->pdo, 'SQL', 'sql');
        Fixtures::createCategory($this->pdo, 'Docker', 'docker');
        $post = Fixtures::createPost($this->pdo, 'Пост', 'post', '2026-01-01 10:00:00');
        Fixtures::attach($this->pdo, $post, $php);
        Fixtures::attach($this->pdo, $post, $sql);

        $categories = (new CategoryRepository($this->pdo))->findByPostId($post);

        self::assertSame([$php, $sql], array_map(static fn ($c) => $c->id, $categories));
    }
}
