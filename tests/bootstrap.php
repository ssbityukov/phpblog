<?php

declare(strict_types=1);

use App\Database\Migrator;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\TestDatabase;

require __DIR__ . '/../vendor/autoload.php';

TestDatabase::name();

(new Migrator(DatabaseTestCase::connect(), __DIR__ . '/../database/migrations'))->fresh();
