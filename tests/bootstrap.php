<?php

declare(strict_types=1);

use App\Database\Migrator;
use App\Tests\Support\DatabaseTestCase;

require __DIR__ . '/../vendor/autoload.php';

// Схема заливается один раз на весь прогон: тесты изолируются транзакцией,
// поэтому пересобирать её на каждый тест-класс незачем.
(new Migrator(DatabaseTestCase::connect(), __DIR__ . '/../database/migrations'))->fresh();
