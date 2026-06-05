{if $CANVA_SETTINGS_SAVED}
  <p class="infos">Media settings saved.</p>
{/if}

<form method="post" action="{$CANVA_FORM_ACTION_SETTINGS|escape:'html'}">
  <fieldset>
    <legend>Canva insert format</legend>
    <p>These settings control the image variant sent to Canva when a user inserts a Piwigo image into a design.</p>
    <ul class="properties">
      <li>
        <span class="property">
          <label for="asset-max-dimension">Maximum dimension</label>
        </span>
        <input id="asset-max-dimension" type="number" name="asset_max_dimension" min="800" max="6000" value="{$CANVA_CONFIG.asset_max_dimension|escape:'html'}">
        <span class="canva-help">px, longest side. 2048 is a good fast default; 3000+ is better for print.</span>
      </li>
      <li>
        <span class="property">
          <label for="asset-quality">JPEG quality</label>
        </span>
        <input id="asset-quality" type="number" name="asset_quality" min="45" max="95" value="{$CANVA_CONFIG.asset_quality|escape:'html'}">
        <span class="canva-help">45-95. Higher is heavier.</span>
      </li>
      <li>
        <span class="property">
          <label for="convert-png-to-jpeg">PNG handling</label>
        </span>
        <select id="convert-png-to-jpeg" name="convert_png_to_jpeg">
          {foreach from=$CANVA_PNG_POLICIES item=policy}
            <option value="{$policy.value|escape:'html'}" {if $CANVA_CONFIG.convert_png_to_jpeg == $policy.value}selected="selected"{/if}>{$policy.label|escape:'html'}</option>
          {/foreach}
        </select>
      </li>
    </ul>
  </fieldset>

  <fieldset>
    <legend>Preview format</legend>
    <p>Preview images are used for browsing and lightweight display.</p>
    <ul class="properties">
      <li>
        <span class="property">
          <label for="preview-max-dimension">Maximum dimension</label>
        </span>
        <input id="preview-max-dimension" type="number" name="preview_max_dimension" min="320" max="2400" value="{$CANVA_CONFIG.preview_max_dimension|escape:'html'}">
        <span class="canva-help">px, longest side.</span>
      </li>
      <li>
        <span class="property">
          <label for="preview-quality">JPEG quality</label>
        </span>
        <input id="preview-quality" type="number" name="preview_quality" min="45" max="95" value="{$CANVA_CONFIG.preview_quality|escape:'html'}">
      </li>
    </ul>
  </fieldset>

  <fieldset>
    <legend>Thumbnail format</legend>
    <p>Thumbnails are the small images shown in the Canva app grid.</p>
    <ul class="properties">
      <li>
        <span class="property">
          <label for="thumb-max-dimension">Maximum dimension</label>
        </span>
        <input id="thumb-max-dimension" type="number" name="thumb_max_dimension" min="120" max="900" value="{$CANVA_CONFIG.thumb_max_dimension|escape:'html'}">
        <span class="canva-help">px, longest side.</span>
      </li>
      <li>
        <span class="property">
          <label for="thumb-quality">JPEG quality</label>
        </span>
        <input id="thumb-quality" type="number" name="thumb_quality" min="45" max="90" value="{$CANVA_CONFIG.thumb_quality|escape:'html'}">
      </li>
    </ul>
  </fieldset>

  <fieldset>
    <legend>Recommended presets</legend>
    <table class="table2">
      <thead>
        <tr class="throw">
          <th>Mode</th>
          <th>Canva insert</th>
          <th>Preview</th>
          <th>Thumbnail</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Fast</td>
          <td>1600px / quality 76</td>
          <td>960px / quality 72</td>
          <td>320px / quality 68</td>
        </tr>
        <tr>
          <td>Balanced</td>
          <td>2048px / quality 82</td>
          <td>1280px / quality 76</td>
          <td>360px / quality 72</td>
        </tr>
        <tr>
          <td>Print</td>
          <td>3000px / quality 88</td>
          <td>1600px / quality 80</td>
          <td>420px / quality 74</td>
        </tr>
      </tbody>
    </table>
  </fieldset>

  <input type="hidden" name="csrf_token" value="{$CANVA_CSRF_TOKEN|escape:'html'}">
  <p class="actionButtons">
    <button type="submit" name="save_settings" value="1" class="button">Save settings</button>
  </p>
</form>
