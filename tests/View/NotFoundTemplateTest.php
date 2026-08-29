<?php

declare(strict_types=1);

namespace App\Tests\View;

use App\Tests\Support\TemplateTestCase;

final class NotFoundTemplateTest extends TemplateTestCase
{
    public function testShowsMessageAndLinkHome(): void
    {
        $html = $this->view->render('404.tpl', ['title' => 'Страница не найдена']);

        self::assertStringContainsString('<h1>Страница не найдена</h1>', $html);
        self::assertStringContainsString('<a href="/">Вернуться на главную</a>', $html);
    }
}
