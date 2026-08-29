<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Core\ErrorPage;
use App\Core\ViewInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorPageTest extends TestCase
{
    public function testRendersTemplate(): void
    {
        $page = new ErrorPage($this->view('<html>rendered</html>'));

        self::assertSame('<html>rendered</html>', $page->render('500.tpl', 'Ошибка сервера'));
    }

    public function testPassesTitleToTemplate(): void
    {
        $view = new class implements ViewInterface {
            /** @var array<string, mixed> */
            public array $vars = [];

            public function render(string $template, array $vars = []): string
            {
                $this->vars = $vars;

                return '';
            }
        };

        (new ErrorPage($view))->render('500.tpl', 'Ошибка сервера');

        self::assertSame(['title' => 'Ошибка сервера'], $view->vars);
    }

    public function testFallsBackToPlainHtmlWhenViewThrows(): void
    {
        $page = new ErrorPage($this->throwingView());

        $html = $page->render('500.tpl', 'Ошибка сервера');

        self::assertStringContainsString('<h1>Ошибка сервера</h1>', $html);
    }

    public function testFallbackLeaksNothingAboutTheFailure(): void
    {
        $page = new ErrorPage($this->throwingView());

        $html = $page->render('500.tpl', 'Ошибка сервера');

        self::assertStringNotContainsString('compile dir', $html);
        self::assertStringNotContainsString('RuntimeException', $html);
    }

    public function testFallbackEscapesTitle(): void
    {
        $page = new ErrorPage($this->throwingView());

        $html = $page->render('500.tpl', '<script>alert(1)</script>');

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    private function view(string $output): ViewInterface
    {
        return new class ($output) implements ViewInterface {
            public function __construct(private readonly string $output)
            {
            }

            public function render(string $template, array $vars = []): string
            {
                return $this->output;
            }
        };
    }

    private function throwingView(): ViewInterface
    {
        return new class implements ViewInterface {
            public function render(string $template, array $vars = []): string
            {
                throw new RuntimeException('unable to write compile dir');
            }
        };
    }
}
