<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\Paginator;
use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase
{
    public function testCalculatesLastPage(): void
    {
        self::assertSame(3, (new Paginator(25, 10, 1))->lastPage());
        self::assertSame(2, (new Paginator(20, 10, 1))->lastPage());
    }

    public function testLastPageIsAtLeastOneWhenNoRecords(): void
    {
        $paginator = new Paginator(0, 10, 1);

        self::assertSame(1, $paginator->lastPage());
        self::assertSame(1, $paginator->page());
        self::assertSame(0, $paginator->offset());
    }

    public function testClampsPageBelowRange(): void
    {
        self::assertSame(1, (new Paginator(25, 10, 0))->page());
        self::assertSame(1, (new Paginator(25, 10, -5))->page());
    }

    public function testClampsPageAboveRange(): void
    {
        self::assertSame(3, (new Paginator(25, 10, 99))->page());
    }

    public function testCalculatesOffset(): void
    {
        self::assertSame(0, (new Paginator(25, 10, 1))->offset());
        self::assertSame(10, (new Paginator(25, 10, 2))->offset());
        self::assertSame(20, (new Paginator(25, 10, 3))->offset());
    }

    public function testKnowsNeighbours(): void
    {
        $first = new Paginator(25, 10, 1);
        self::assertFalse($first->hasPrevious());
        self::assertTrue($first->hasNext());

        $last = new Paginator(25, 10, 3);
        self::assertTrue($last->hasPrevious());
        self::assertFalse($last->hasNext());
    }

    public function testListsAllPageNumbers(): void
    {
        self::assertSame([1, 2, 3], (new Paginator(25, 10, 1))->pages());
    }

    public function testRejectsZeroPerPage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Per page must be positive, got 0.');

        new Paginator(25, 0, 1);
    }

    public function testRejectsNegativePerPage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Paginator(25, -10, 1);
    }
}
