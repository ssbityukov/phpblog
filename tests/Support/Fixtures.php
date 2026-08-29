<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PDO;

final class Fixtures
{
    public static function createCategory(PDO $pdo, string $name, string $slug): int
    {
        $statement = $pdo->prepare(
            'INSERT INTO categories (slug, name, description) VALUES (:slug, :name, :description)'
        );
        $statement->execute([
            'slug' => $slug,
            'name' => $name,
            'description' => $name . ' description',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function createPost(
        PDO $pdo,
        string $title,
        string $slug,
        string $publishedAt,
        int $views = 0,
    ): int {
        $statement = $pdo->prepare(
            'INSERT INTO posts (slug, title, description, body, image, views, published_at)
             VALUES (:slug, :title, :description, :body, :image, :views, :published_at)'
        );
        $statement->execute([
            'slug' => $slug,
            'title' => $title,
            'description' => $title . ' description',
            'body' => $title . ' body',
            'image' => null,
            'views' => $views,
            'published_at' => $publishedAt,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function attach(PDO $pdo, int $postId, int $categoryId): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO post_category (post_id, category_id) VALUES (:post_id, :category_id)'
        );
        $statement->execute(['post_id' => $postId, 'category_id' => $categoryId]);
    }
}
