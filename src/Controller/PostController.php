<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Seo;
use App\Core\ViewInterface;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;

final class PostController
{
    private const SIMILAR_LIMIT = 3;

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly PostRepository $posts,
        private readonly ViewInterface $view,
        private readonly Seo $seo,
    ) {
    }

    public function show(Request $request, string $slug): string
    {
        $post = $this->posts->findBySlug($slug);

        if ($post === null) {
            throw new NotFoundException(sprintf('Post "%s" not found.', $slug));
        }

        $categories = $this->categories->findByPostId($post->id);
        $categoryIds = array_map(static fn ($category) => $category->id, $categories);

        if ($request->method() !== 'HEAD') {
            $this->posts->incrementViews($post->id);
        }

        return $this->view->render('post.tpl', [
            'title' => $post->title,
            'description' => $this->seo->description($post->description),
            'canonical' => $this->seo->canonical('/post/' . $post->slug),
            'post' => $post,
            'views' => $post->views + 1,
            'categories' => $categories,
            'similar' => $this->posts->findSimilar($post->id, $categoryIds, self::SIMILAR_LIMIT),
        ]);
    }
}
