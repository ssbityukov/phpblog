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
