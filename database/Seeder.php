<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final class Seeder
{
    private const POST_COUNT = 60;

    private const CATEGORIES = [
        ['php', 'PHP', 'Язык, на котором написан этот сайт: синтаксис, стандартная библиотека, приёмы.'],
        ['mysql', 'MySQL', 'Схемы, индексы, планы запросов и всё, что помогает базе отвечать быстро.'],
        ['docker', 'Docker', 'Контейнеры для разработки: образы, compose, отладка окружения.'],
        ['frontend', 'Frontend', 'Вёрстка, SCSS, доступность и работа с браузером без тяжёлых фреймворков.'],
        ['tools', 'Инструменты', 'Git, командная строка и всё, что ускоряет ежедневную работу.'],
        ['architecture', 'Архитектура', 'Границы модулей, слои приложения и решения, о которых не жалеешь через год.'],
    ];

    /**
     * Шаблон заголовка и падеж, которого он требует от темы.
     * Без падежей выходило «Пять приёмов работы с структуру проекта».
     */
    private const TITLE_TEMPLATES = [
        ['Что нужно знать про %s', self::CASE_ACCUSATIVE],
        ['Разбираем %s', self::CASE_ACCUSATIVE],
        ['Практическое введение в %s', self::CASE_ACCUSATIVE],
        ['Оптимизируем %s', self::CASE_ACCUSATIVE],
        ['Готовим %s', self::CASE_ACCUSATIVE],
        ['Как ускорить %s', self::CASE_ACCUSATIVE],
        ['Пять приёмов работы %s', self::CASE_INSTRUMENTAL],
        ['Частые ошибки при работе %s', self::CASE_INSTRUMENTAL],
    ];

    private const CASE_ACCUSATIVE = 0;
    private const CASE_INSTRUMENTAL = 1;

    /** Тема статьи в винительном и творительном падеже. */
    private const TITLE_SUBJECTS = [
        ['подготовленные запросы', 'подготовленными запросами'],
        ['индексы в MySQL', 'индексами в MySQL'],
        ['контейнеры для разработки', 'контейнерами для разработки'],
        ['автозагрузку классов', 'автозагрузкой классов'],
        ['шаблонизацию', 'шаблонизацией'],
        ['пагинацию', 'пагинацией'],
        ['связи многие-ко-многим', 'связями многие-ко-многим'],
        ['сборку стилей', 'сборкой стилей'],
        ['структуру проекта', 'структурой проекта'],
        ['обработку ошибок', 'обработкой ошибок'],
    ];

    private const PARAGRAPHS = [
        'Задача выглядит простой ровно до того момента, пока не появляются реальные данные и реальная нагрузка.',
        'Разберём решение по шагам: сначала минимальный работающий вариант, затем места, где он ломается.',
        'Главное правило — держать границы модулей на виду: пока понятно, кто за что отвечает, изменения дешёвы.',
        'Проверка на практике важнее теории: пишем тест, убеждаемся, что он падает, и только потом правим код.',
        'Итог: решение занимает несколько десятков строк и не тянет за собой лишних зависимостей.',
    ];

    /**
     * Уникальные заголовки: перебираем все сочетания шаблона и темы,
     * перемешиваем и берём нужное количество.
     *
     * @return list<string>
     */
    public static function titles(int $count): array
    {
        $combinations = [];

        foreach (self::TITLE_TEMPLATES as [$template, $grammaticalCase]) {
            foreach (self::TITLE_SUBJECTS as [$accusative, $instrumental]) {
                $combinations[] = $grammaticalCase === self::CASE_INSTRUMENTAL
                    ? sprintf($template, self::preposition($instrumental) . ' ' . $instrumental)
                    : sprintf($template, $accusative);
            }
        }

        if ($count > count($combinations)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot build %d unique titles, only %d combinations exist.',
                $count,
                count($combinations),
            ));
        }

        shuffle($combinations);

        return array_slice($combinations, 0, $count);
    }

    /** «Со» перед стечением согласных: со структурой, со сборкой, со связями. */
    private static function preposition(string $word): string
    {
        return preg_match('/^[сзшж][бвгджзклмнпрстфхцчшщ]/u', $word) === 1 ? 'со' : 'с';
    }

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $imageDirectory,
    ) {
    }

    public function run(bool $fresh): void
    {
        if ($this->hasData()) {
            if (!$fresh) {
                throw new RuntimeException('Database is not empty. Use "seed --fresh" to wipe and reseed.');
            }

            $this->wipe();
        }

        if (!is_dir($this->imageDirectory) && !mkdir($this->imageDirectory, 0777, true)) {
            throw new RuntimeException(sprintf('Cannot create directory "%s".', $this->imageDirectory));
        }

        $this->pdo->beginTransaction();

        try {
            $categoryIds = $this->seedCategories();
            $this->seedPosts($categoryIds);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            $this->pdo->rollBack();

            throw $error;
        }
    }

    private function hasData(): bool
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn() > 0
            || (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() > 0;
    }

    private function wipe(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('TRUNCATE TABLE post_category');
        $this->pdo->exec('TRUNCATE TABLE posts');
        $this->pdo->exec('TRUNCATE TABLE categories');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        foreach (glob($this->imageDirectory . '/*.png') ?: [] as $file) {
            unlink($file);
        }
    }

    /** @return list<int> */
    private function seedCategories(): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (slug, name, description) VALUES (:slug, :name, :description)'
        );

        $ids = [];

        foreach (self::CATEGORIES as [$slug, $name, $description]) {
            $statement->execute(['slug' => $slug, 'name' => $name, 'description' => $description]);
            $ids[] = (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /** @param list<int> $categoryIds */
    private function seedPosts(array $categoryIds): void
    {
        $insertPost = $this->pdo->prepare(
            'INSERT INTO posts (slug, title, description, body, image, views, published_at)
             VALUES (:slug, :title, :description, :body, :image, :views, :published_at)'
        );
        $attach = $this->pdo->prepare(
            'INSERT INTO post_category (post_id, category_id) VALUES (:post_id, :category_id)'
        );

        $titles = self::titles(self::POST_COUNT);

        for ($index = 1; $index <= self::POST_COUNT; $index++) {
            $title = $titles[$index - 1];
            $image = $this->createImage($index);

            $insertPost->execute([
                'slug' => 'statya-' . $index,
                'title' => $title,
                'description' => self::PARAGRAPHS[$index % count(self::PARAGRAPHS)],
                'body' => $this->buildBody(),
                'image' => $image,
                'views' => random_int(0, 5000),
                'published_at' => date('Y-m-d H:i:s', strtotime(sprintf('-%d days', random_int(0, 180)))),
            ]);

            $postId = (int) $this->pdo->lastInsertId();

            foreach ($this->pickCategories($categoryIds) as $categoryId) {
                $attach->execute(['post_id' => $postId, 'category_id' => $categoryId]);
            }
        }
    }

    /**
     * @param list<int> $categoryIds
     * @return list<int>
     */
    private function pickCategories(array $categoryIds): array
    {
        $keys = (array) array_rand($categoryIds, random_int(1, 3));

        return array_values(array_map(static fn ($key) => $categoryIds[$key], $keys));
    }

    private function buildBody(): string
    {
        $paragraphs = self::PARAGRAPHS;
        shuffle($paragraphs);

        return implode("\n\n", $paragraphs);
    }

    private function createImage(int $index): string
    {
        $image = imagecreatetruecolor(600, 400);
        $background = imagecolorallocate($image, 40 + ($index * 17) % 180, 60 + ($index * 29) % 160, 120 + ($index * 41) % 120);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, 600, 400, $background);
        imagestring($image, 5, 20, 20, 'POST #' . $index, $white);

        $name = sprintf('post-%d.png', $index);
        imagepng($image, $this->imageDirectory . '/' . $name);
        imagedestroy($image);

        return $name;
    }
}
