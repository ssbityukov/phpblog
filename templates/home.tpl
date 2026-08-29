{extends file="layout.tpl"}

{block name="content"}
    <h1 class="page-title">Последние статьи</h1>

    {foreach $categories as $category}
        <section class="category-block">
            <div class="category-block__head">
                <h2 class="category-block__title">
                    <a href="/category/{$category->slug}">{$category->name}</a>
                </h2>
                <a class="button" href="/category/{$category->slug}">Все статьи</a>
            </div>

            <div class="cards">
                {foreach $postsByCategory[$category->id] as $post}
                    {include file="partials/post_card.tpl" post=$post}
                {/foreach}
            </div>
        </section>
    {foreachelse}
        <p>Статей пока нет. Запустите <code>php bin/console seed</code>.</p>
    {/foreach}
{/block}
