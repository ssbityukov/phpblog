<?php

declare(strict_types=1);

namespace App\Tests\View;

use App\Tests\Support\TemplateTestCase;

final class ServerErrorTemplateTest extends TemplateTestCase
{
    public function testShowsMessageAndLinkHome(): void
    {
        $html = $this->view->render('500.tpl', ['title' => 'Ошибка сервера']);

        self::assertStringContainsString('<h1>Ошибка сервера</h1>', $html);
        self::assertStringContainsString('<a href="/">Вернуться на главную</a>', $html);
    }

    public function testUsesSiteLayout(): void
    {
        $html = $this->view->render('500.tpl', ['title' => 'Ошибка сервера']);

        self::assertStringContainsString('<header class="header">', $html);
        self::assertStringContainsString('<footer class="footer">', $html);
    }

    public function testLeaksNoDiagnostics(): void
    {
        $html = $this->view->render('500.tpl', ['title' => 'Ошибка сервера']);

        self::assertStringNotContainsString('/var/www', $html);
        self::assertStringNotContainsString('Exception', $html);
    }
}
