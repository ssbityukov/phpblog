<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Core\View;
use App\Model\Category;
use App\Model\Post;
use PHPUnit\Framework\TestCase;

/** Рендерит настоящие шаблоны настоящим Smarty, с компиляцией во временный каталог. */
abstract class TemplateTestCase extends TestCase
{
    private static ?string $tempDir = null;

    protected View $view;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $temp = self::tempDir();

        $this->view = new View($root . '/templates', $temp . '/compile', $temp . '/cache');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tempDir === null) {
            return;
        }

        foreach (['compile', 'cache'] as $subdir) {
            foreach (glob(self::$tempDir . '/' . $subdir . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir(self::$tempDir . '/' . $subdir);
        }

        rmdir(self::$tempDir);
        self::$tempDir = null;
    }

    protected static function post(
        string $title = 'Мой пост',
        string $slug = 'my-post',
        int $views = 5,
        ?string $image = null,
        string $body = 'Тело поста',
    ): Post {
        return new Post(1, $slug, $title, $title . ' description', $body, $image, $views, '2026-01-10 10:00:00');
    }

    protected static function category(string $name = 'PHP', string $slug = 'php'): Category
    {
        return new Category(1, $slug, $name, $name . ' description');
    }

    private static function tempDir(): string
    {
        if (self::$tempDir !== null) {
            return self::$tempDir;
        }

        $dir = sys_get_temp_dir() . '/smarty-test-' . bin2hex(random_bytes(6));
        mkdir($dir . '/compile', 0o777, true);
        mkdir($dir . '/cache', 0o777, true);

        return self::$tempDir = $dir;
    }
}
