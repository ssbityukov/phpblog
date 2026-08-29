<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\NotFoundException;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesStaticRoute(): void
    {
        $router = new Router();
        $router->add('/', ['HomeController', 'index']);

        $match = $router->match('/');

        self::assertSame(['HomeController', 'index'], $match['handler']);
        self::assertSame([], $match['params']);
    }

    public function testExtractsNamedParameter(): void
    {
        $router = new Router();
        $router->add('/category/{slug}', ['CategoryController', 'show']);

        $match = $router->match('/category/php-tips');

        self::assertSame(['CategoryController', 'show'], $match['handler']);
        self::assertSame(['slug' => 'php-tips'], $match['params']);
    }

    public function testDoesNotMatchExtraSegments(): void
    {
        $router = new Router();
        $router->add('/category/{slug}', ['CategoryController', 'show']);

        $this->expectException(NotFoundException::class);
        $router->match('/category/php/extra');
    }

    public function testThrowsWhenNoRouteMatches(): void
    {
        $router = new Router();
        $router->add('/', ['HomeController', 'index']);

        $this->expectException(NotFoundException::class);
        $router->match('/unknown');
    }

    public function testFirstMatchingRouteWins(): void
    {
        $router = new Router();
        $router->add('/post/{slug}', ['PostController', 'show']);
        $router->add('/post/{other}', ['OtherController', 'show']);

        self::assertSame(['PostController', 'show'], $router->match('/post/hello')['handler']);
    }
}
