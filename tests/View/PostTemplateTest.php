<?php

declare(strict_types=1);

namespace App\Tests\View;

use App\Model\Post;
use App\Tests\Support\TemplateTestCase;

final class PostTemplateTest extends TemplateTestCase
{
    public function testShowsTitleDateViewsAndCategories(): void
    {
        $html = $this->render();

        self::assertStringContainsString('<h1 class="post__title">Мой пост</h1>', $html);
        self::assertStringContainsString('10.01.2026', $html);
        self::assertStringContainsString('6 просмотров', $html);
        self::assertStringContainsString('<a class="tag" href="/category/php">PHP</a>', $html);
    }

    public function testShowsImageOnlyWhenPostHasOne(): void
    {
        self::assertStringContainsString(
            '<img class="post__image" src="/uploads/posts/cover.webp"',
            $this->render(self::post(image: 'cover.webp')),
        );
        self::assertStringNotContainsString('post__image', $this->render());
    }

    public function testKeepsLineBreaksButEscapesHtmlInBody(): void
    {
        $html = $this->render(self::post(body: "Первая строка\n<script>alert(1)</script>"));

        self::assertStringContainsString('Первая строка<br />', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRendersSimilarPostsBlock(): void
    {
        $html = $this->render(similar: [self::post('Похожий', 'similar-post')]);

        self::assertStringContainsString('Похожие статьи', $html);
        self::assertStringContainsString('<a href="/post/similar-post">Похожий</a>', $html);
    }

    public function testHidesSimilarBlockWhenThereAreNone(): void
    {
        self::assertStringNotContainsString('Похожие статьи', $this->render());
    }

    /** @param list<Post> $similar */
    private function render(?Post $post = null, array $similar = []): string
    {
        $post ??= self::post();

        return $this->view->render('post.tpl', [
            'title' => $post->title,
            'description' => $post->description,
            'canonical' => 'http://localhost:8080/post/' . $post->slug,
            'post' => $post,
            'views' => $post->views + 1,
            'categories' => [self::category()],
            'similar' => $similar,
        ]);
    }
}
