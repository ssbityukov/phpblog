<?php

declare(strict_types=1);

namespace App\Tests\Support;

use RuntimeException;

final class TestDatabase
{
    private const SUFFIX = '_test';

    public static function name(): string
    {
        $name = getenv('DB_DATABASE');
        $name = is_string($name) && $name !== '' ? $name : 'blog_test';

        self::guard($name);

        return $name;
    }

    public static function guard(string $name): void
    {
        if (!str_ends_with($name, self::SUFFIX)) {
            throw new RuntimeException(sprintf(
                'Отказываюсь запускать тесты на базе "%s": бутстрап сносит схему целиком, '
                . 'а имя тестовой базы должно оканчиваться на "%s".',
                $name,
                self::SUFFIX,
            ));
        }
    }
}
