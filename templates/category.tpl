{extends file="layout.tpl"}

{block name="content"}
    <h1 class="page-title">{$category->name}</h1>
    <p class="page-description">{$category->description}</p>

    <div class="sort">
        <span class="sort__label">Сортировка:</span>
        <a class="sort__link{if $sort == 'date'} sort__link--active{/if}"
           href="/category/{$category->slug}?sort=date">по дате</a>
        <a class="sort__link{if $sort == 'views'} sort__link--active{/if}"
           href="/category/{$category->slug}?sort=views">по просмотрам</a>
    </div>

    <div class="cards">
        {foreach $posts as $post}
            {include file="partials/post_card.tpl" post=$post}
        {foreachelse}
            <p>В этой категории пока нет статей.</p>
        {/foreach}
    </div>

    {assign var="baseUrl" value="/category/`$category->slug`"}
    {include file="partials/pagination.tpl" baseUrl=$baseUrl sort=$sort paginator=$paginator}
{/block}
