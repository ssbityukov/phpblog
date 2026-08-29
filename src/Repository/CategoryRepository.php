<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;
use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<Category> */
    public function findAllWithPosts(): array
    {
        $statement = $this->pdo->query(
            'SELECT c.id, c.slug, c.name, c.description
             FROM categories c
             JOIN post_category pc ON pc.category_id = c.id
             GROUP BY c.id, c.slug, c.name, c.description
             ORDER BY c.name'
        );

        return array_map(Category::fromRow(...), $statement->fetchAll());
    }

    public function findBySlug(string $slug): ?Category
    {
        $statement = $this->pdo->prepare(
            'SELECT id, slug, name, description FROM categories WHERE slug = :slug'
        );
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();

        return $row === false ? null : Category::fromRow($row);
    }

    /** @return list<Category> */
    public function findByPostId(int $postId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.slug, c.name, c.description
             FROM categories c
             JOIN post_category pc ON pc.category_id = c.id
             WHERE pc.post_id = :post_id
             ORDER BY c.id'
        );
        $statement->execute(['post_id' => $postId]);

        return array_map(Category::fromRow(...), $statement->fetchAll());
    }
}
