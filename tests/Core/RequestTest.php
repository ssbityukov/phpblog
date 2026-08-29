<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\Request;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testStripsQueryStringFromPath(): void
    {
        $request = new Request('/category/php?sort=views&page=2', ['sort' => 'views', 'page' => '2']);

        self::assertSame('/category/php', $request->path());
    }

    public function testReturnsQueryValue(): void
    {
        $request = new Request('/', ['sort' => 'views']);

        self::assertSame('views', $request->query('sort'));
        self::assertSame('date', $request->query('missing', 'date'));
    }

    public function testCastsQueryValueToInt(): void
    {
        $request = new Request('/', ['page' => '3']);

        self::assertSame(3, $request->queryInt('page', 1));
    }

    public function testFallsBackToDefaultForNonNumericInt(): void
    {
        $request = new Request('/', ['page' => 'abc']);

        self::assertSame(1, $request->queryInt('page', 1));
    }

    public function testRejectsInjectionPayloadInPageParameter(): void
    {
        $request = new Request('/category/php?page=1;DROP TABLE posts', ['page' => '1;DROP TABLE posts']);

        self::assertSame(1, $request->queryInt('page', 1));
    }

    public function testNormalisesTrailingSlash(): void
    {
        $request = new Request('/category/php/', []);

        self::assertSame('/category/php', $request->path());
    }

    #[BackupGlobals(true)]
    public function testBuildsRequestFromGlobals(): void
    {
        $_SERVER['REQUEST_URI'] = '/category/php?sort=views';
        $_GET = ['sort' => 'views'];

        $request = Request::fromGlobals();

        self::assertSame('/category/php', $request->path());
        self::assertSame('views', $request->query('sort'));
    }

    #[BackupGlobals(true)]
    public function testIgnoresArrayQueryParameters(): void
    {
        $_SERVER['REQUEST_URI'] = '/category/php?page[]=1&sort=views';
        $_GET = ['page' => ['1'], 'sort' => 'views'];

        $request = Request::fromGlobals();

        self::assertSame(1, $request->queryInt('page', 1));
        self::assertSame('', $request->query('page'));
        self::assertSame('views', $request->query('sort'));
    }

    public function testDefaultsToGetMethod(): void
    {
        self::assertSame('GET', (new Request('/', []))->method());
    }

    public function testReturnsGivenMethod(): void
    {
        self::assertSame('HEAD', (new Request('/', [], 'HEAD'))->method());
    }

    public function testNormalisesMethodToUpperCase(): void
    {
        self::assertSame('HEAD', (new Request('/', [], 'head'))->method());
    }

    #[BackupGlobals(true)]
    public function testReadsMethodFromGlobals(): void
    {
        $_SERVER['REQUEST_URI'] = '/post/hello';
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_GET = [];

        self::assertSame('HEAD', Request::fromGlobals()->method());
    }

    #[BackupGlobals(true)]
    public function testFallsBackToGetWhenMethodMissing(): void
    {
        $_SERVER['REQUEST_URI'] = '/post/hello';
        unset($_SERVER['REQUEST_METHOD']);
        $_GET = [];

        self::assertSame('GET', Request::fromGlobals()->method());
    }
}
