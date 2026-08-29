<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Post;
use PDO;

final class PostRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @var array<string, string> */
    public const SORTS = [
        'date' => 'p.published_at DESC, p.id DESC',
        'views' => 'p.views DESC, p.id DESC',
    ];

    public function countByCategory(int $categoryId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM post_category WHERE category_id = :category_id'
        );
        $statement->execute(['category_id' => $categoryId]);

        return (int) $statement->fetchColumn();
    }

    /** @return list<Post> */
    public function findByCategory(int $categoryId, string $sort, int $limit, int $offset): array
    {
        $orderBy = self::SORTS[$sort] ?? self::SORTS['date'];

        $statement = $this->pdo->prepare(
            'SELECT p.*
             FROM posts p
             JOIN post_category pc ON pc.post_id = p.id
             WHERE pc.category_id = :category_id
             ORDER BY ' . $orderBy . '
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map(Post::fromRow(...), $statement->fetchAll());
    }

    public function findBySlug(string $slug): ?Post
    {
        $statement = $this->pdo->prepare('SELECT * FROM posts WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();

        return $row === false ? null : Post::fromRow($row);
    }

    public function incrementViews(int $postId): void
    {
        $statement = $this->pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = :id');
        $statement->execute(['id' => $postId]);
    }

    /**
     * @param list<int> $categoryIds
     * @return list<Post>
     */
    public function findSimilar(int $postId, array $categoryIds, int $limit = 3): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_values($categoryIds) as $index => $categoryId) {
            $placeholders[] = ':category_' . $index;
            $parameters['category_' . $index] = $categoryId;
        }

        $statement = $this->pdo->prepare(
            'SELECT p.*, COUNT(*) AS shared
             FROM posts p
             JOIN post_category pc ON pc.post_id = p.id
             WHERE pc.category_id IN (' . implode(', ', $placeholders) . ')
               AND p.id <> :post_id
             GROUP BY p.id
             ORDER BY shared DESC, p.published_at DESC, p.id DESC
             LIMIT :limit'
        );

        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value, PDO::PARAM_INT);
        }

        $statement->bindValue('post_id', $postId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(Post::fromRow(...), $statement->fetchAll());
    }

    /** @return array<int, list<Post>> */
    public function latestByCategories(int $limit = 3): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM (
                 SELECT p.*, pc.category_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY pc.category_id ORDER BY p.published_at DESC, p.id DESC
                        ) AS rn
                 FROM posts p
                 JOIN post_category pc ON pc.post_id = p.id
             ) ranked
             WHERE ranked.rn <= :limit
             ORDER BY ranked.category_id, ranked.rn'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $grouped[(int) $row['category_id']][] = Post::fromRow($row);
        }

        return $grouped;
    }
}
