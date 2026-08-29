{extends file="layout.tpl"}

{block name="content"}
    <article class="post">
        {if $post->image}
            <img class="post__image" src="/uploads/posts/{$post->image}" alt="{$post->title}">
        {/if}

        <h1 class="post__title">{$post->title}</h1>

        <p class="post__meta">
            {$post->publishedDate()} · {$views} просмотров
        </p>

        <p class="post__categories">
            {foreach $categories as $category}
                <a class="tag" href="/category/{$category->slug}">{$category->name}</a>
            {/foreach}
        </p>

        <p class="post__description">{$post->description}</p>

        <div class="post__body">{$post->bodyHtml() nofilter}</div>
    </article>

    {if $similar}
        <section class="similar">
            <h2>Похожие статьи</h2>
            <div class="cards">
                {foreach $similar as $post}
                    {include file="partials/post_card.tpl" post=$post}
                {/foreach}
            </div>
        </section>
    {/if}
{/block}
