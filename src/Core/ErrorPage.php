<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class ErrorPage
{
    public function __construct(private readonly ViewInterface $view)
    {
    }

    public function render(string $template, string $title): string
    {
        try {
            return $this->view->render($template, ['title' => $title]);
        } catch (Throwable) {
            $safe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

            return '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
                . '<title>' . $safe . '</title></head><body>'
                . '<h1>' . $safe . '</h1>'
                . '<p><a href="/">Вернуться на главную</a></p>'
                . '</body></html>';
        }
    }
}
