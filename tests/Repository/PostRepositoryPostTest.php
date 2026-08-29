<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\PostRepository;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\Fixtures;

final class PostRepositoryPostTest extends DatabaseTestCase
{
    public function testFindsPostBySlug(): void
    {
        $id = Fixtures::createPost($this->pdo, 'Заголовок', 'zagolovok', '2026-01-01 10:00:00', 42);

        $post = (new PostRepository($this->pdo))->findBySlug('zagolovok');

        self::assertNotNull($post);
        self::assertSame($id, $post->id);
        self::assertSame('Заголовок', $post->title);
        self::assertSame(42, $post->views);
        self::assertSame('Заголовок body', $post->body);
    }

    public function testReturnsNullForUnknownSlug(): void
    {
        self::assertNull((new PostRepository($this->pdo))->findBySlug('missing'));
    }

    public function testSlugWithSqlPayloadFindsNothingAndLeavesTableIntact(): void
    {
        Fixtures::createPost($this->pdo, 'Пост', 'post', '2026-01-01 10:00:00');

        $post = (new PostRepository($this->pdo))->findBySlug("' OR 1=1 -- ");

        self::assertNull($post);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn());
    }

    public function testIncrementsViews(): void
    {
        $id = Fixtures::createPost($this->pdo, 'Пост', 'post', '2026-01-01 10:00:00', 5);

        (new PostRepository($this->pdo))->incrementViews($id);

        $statement = $this->pdo->prepare('SELECT views FROM posts WHERE id = :id');
        $statement->execute(['id' => $id]);

        self::assertSame(6, (int) $statement->fetchColumn());
    }

    public function testRanksSimilarPostsByNumberOfSharedCategories(): void
    {
        $php = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        $sql = Fixtures::createCategory($this->pdo, 'SQL', 'sql');
        $docker = Fixtures::createCategory($this->pdo, 'Docker', 'docker');

        $current = Fixtures::createPost($this->pdo, 'Текущий', 'current', '2026-05-01 10:00:00');
        Fixtures::attach($this->pdo, $current, $php);
        Fixtures::attach($this->pdo, $current, $sql);

        $twoShared = Fixtures::createPost($this->pdo, 'Две общие', 'two', '2026-01-01 10:00:00');
        Fixtures::attach($this->pdo, $twoShared, $php);
        Fixtures::attach($this->pdo, $twoShared, $sql);

        $oneShared = Fixtures::createPost($this->pdo, 'Одна общая', 'one', '2026-04-01 10:00:00');
        Fixtures::attach($this->pdo, $oneShared, $php);

        $unrelated = Fixtures::createPost($this->pdo, 'Чужой', 'unrelated', '2026-04-02 10:00:00');
        Fixtures::attach($this->pdo, $unrelated, $docker);

        $similar = (new PostRepository($this->pdo))->findSimilar($current, [$php, $sql], 3);
        $ids = array_map(static fn ($post) => $post->id, $similar);

        self::assertSame([$twoShared, $oneShared], $ids);
    }

    public function testExcludesCurrentPostAndRespectsLimit(): void
    {
        $php = Fixtures::createCategory($this->pdo, 'PHP', 'php');
        $current = Fixtures::createPost($this->pdo, 'Текущий', 'current', '2026-05-01 10:00:00');
        Fixtures::attach($this->pdo, $current, $php);

        foreach (range(1, 4) as $index) {
            $post = Fixtures::createPost($this->pdo, "Пост {$index}", "post-{$index}", "2026-0{$index}-01 10:00:00");
            Fixtures::attach($this->pdo, $post, $php);
        }

        $similar = (new PostRepository($this->pdo))->findSimilar($current, [$php], 3);
        $ids = array_map(static fn ($post) => $post->id, $similar);

        self::assertCount(3, $similar);
        self::assertNotContains($current, $ids);
    }

    public function testReturnsNoSimilarPostsWhenPostHasNoCategories(): void
    {
        $post = Fixtures::createPost($this->pdo, 'Одинокий', 'lonely', '2026-01-01 10:00:00');

        self::assertSame([], (new PostRepository($this->pdo))->findSimilar($post, [], 3));
    }
}
