<div class="titrePage">
  <h2>Canva Connector</h2>
</div>

<style>
  .canva-connector-tabs li { display: inline-block; margin-right: 1em; }
  .canva-help { opacity: 0.75; margin-left: 0.5em; }
</style>

<div class="canva-connector-admin">
  <ul class="categoryActions canva-connector-tabs">
    {foreach from=$CANVA_TABS item=tab}
      <li>
        {if $tab.active}
          <strong>{$tab.label|escape:'html'}</strong>
        {else}
          <a href="{$tab.url|escape:'html'}">{$tab.label|escape:'html'}</a>
        {/if}
      </li>
    {/foreach}
  </ul>

  {if $CANVA_ACTIVE_TAB == 'tokens'}
    {include file=$CANVA_TOKENS_TEMPLATE}
  {/if}

  {if $CANVA_ACTIVE_TAB == 'settings'}
    {include file=$CANVA_SETTINGS_TEMPLATE}
  {/if}
</div>
