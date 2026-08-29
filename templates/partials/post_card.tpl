<article class="card">
    {if $post->image}
        <a class="card__image" href="/post/{$post->slug}">
            <img src="/uploads/posts/{$post->image}" alt="{$post->title}">
        </a>
    {/if}
    <div class="card__body">
        <h3 class="card__title">
            <a href="/post/{$post->slug}">{$post->title}</a>
        </h3>
        <p class="card__description">{$post->description}</p>
        <p class="card__meta">
            {$post->publishedDate()} · {$post->views} просмотров
        </p>
    </div>
</article>
