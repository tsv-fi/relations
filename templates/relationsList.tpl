<section class="item relations">
    <h2 class="label">{translate key="plugins.generic.relations.relations"}</h2>
    <div class="value">
        <ul>
            {foreach from=$relations item="relation"}
                <li>
                    {translate key="plugins.generic.relations.relationType.`$relation.relationType`"} &mdash;
                    {$relation.identifierType}:
                    {if $relation.identifierType == 'DOI'}
                        <a href="https://doi.org/{$relation.value|escape}" target="_blank" rel="noopener">{$relation.value|escape}</a>
                    {elseif $relation.identifierType == 'URI'}
                        <a href="{$relation.value|escape}" target="_blank" rel="noopener">{$relation.value|escape}</a>
                    {elseif $relation.identifierType == 'Handle'}
                        <a href="https://hdl.handle.net/{$relation.value|escape}" target="_blank" rel="noopener">{$relation.value|escape}</a>
                    {elseif $relation.identifierType == 'ARXIV'}
                        <a href="https://arxiv.org/abs/{$relation.value|escape}" target="_blank" rel="noopener">{$relation.value|escape}</a>
                    {elseif $relation.identifierType == 'PMID'}
                        <a href="https://pubmed.ncbi.nlm.nih.gov/{$relation.value|escape}" target="_blank" rel="noopener">{$relation.value|escape}</a>
                    {else}
                        {$relation.value|escape}
                    {/if}
                </li>
            {/foreach}
        </ul>
    </div>
</section>