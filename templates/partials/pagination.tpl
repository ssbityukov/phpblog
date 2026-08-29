{if $paginator->lastPage() > 1}
    <nav class="pagination">
        {if $paginator->hasPrevious()}
            <a class="pagination__link" href="{$baseUrl}?sort={$sort}&amp;page={$paginator->page() - 1}">Назад</a>
        {/if}

        {foreach $paginator->pages() as $number}
            {if $number == $paginator->page()}
                <span class="pagination__link pagination__link--current">{$number}</span>
            {else}
                <a class="pagination__link" href="{$baseUrl}?sort={$sort}&amp;page={$number}">{$number}</a>
            {/if}
        {/foreach}

        {if $paginator->hasNext()}
            <a class="pagination__link" href="{$baseUrl}?sort={$sort}&amp;page={$paginator->page() + 1}">Вперёд</a>
        {/if}
    </nav>
{/if}
