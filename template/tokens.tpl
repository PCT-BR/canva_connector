<fieldset>
  <legend>Connection details</legend>
  <p>This connector lets the Canva Piwigo Media app access this Piwigo instance without sharing Piwigo API keys with a central backend.</p>
  <p><strong>Piwigo URL to enter in Canva:</strong></p>
  <p><code>{$CANVA_BASE_URL|escape:'html'}</code></p>
</fieldset>

{if $CANVA_NEW_TOKEN}
  <fieldset>
    <legend>Connection authorized</legend>
    <p class="infos">Copy this token into Canva now. It is shown only once.</p>
    <p><code id="canva-new-token">{$CANVA_NEW_TOKEN|escape:'html'}</code></p>
    <p>
      <button type="button" class="button" onclick="canvaConnectorCopyToken()">Copy token</button>
    </p>
  </fieldset>
{/if}

{if $CANVA_TOKEN_REVOKED}
  <p class="infos">Token revoked.</p>
{/if}

<form method="post" action="{$CANVA_FORM_ACTION_TOKENS|escape:'html'}">
  <fieldset>
    <legend>Authorize Canva access</legend>
    <p>Canva Piwigo Media will be allowed to:</p>
    <ul>
      <li>list albums from this Piwigo instance</li>
      <li>read photo files that you insert into Canva</li>
      <li>upload Canva exports to the album you select in Canva</li>
    </ul>
    <p>Canva Piwigo Media will not receive your Piwigo username, password, or Piwigo API keys.</p>

    <input type="hidden" name="csrf_token" value="{$CANVA_CSRF_TOKEN|escape:'html'}">
    <p>
      <label for="canva-token-label">Token label</label>
      <input id="canva-token-label" name="label" value="Canva Piwigo Media" maxlength="80" size="40">
    </p>
    <p class="actionButtons">
      <button type="submit" name="authorize" value="1" class="button">Authorize and generate token</button>
    </p>
  </fieldset>
</form>

<fieldset>
  <legend>Existing tokens</legend>
  <table class="table2">
    <thead>
      <tr class="throw">
        <th>Label</th>
        <th>Created</th>
        <th>Last used</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$CANVA_TOKENS item=token}
        <tr>
          <td>{$token.label|escape:'html'}</td>
          <td>{$token.created_at|escape:'html'}</td>
          <td>{if $token.last_used_at}{$token.last_used_at|escape:'html'}{else}-{/if}</td>
          <td>{if $token.revoked_at}Revoked{else}Active{/if}</td>
          <td>
            {if not $token.revoked_at}
              <form method="post" action="{$CANVA_FORM_ACTION_TOKENS|escape:'html'}">
                <input type="hidden" name="csrf_token" value="{$CANVA_CSRF_TOKEN|escape:'html'}">
                <button type="submit" name="revoke" value="{$token.id|escape:'html'}" class="button">Revoke</button>
              </form>
            {/if}
          </td>
        </tr>
      {foreachelse}
        <tr>
          <td colspan="5">No token has been generated yet.</td>
        </tr>
      {/foreach}
    </tbody>
  </table>
</fieldset>

<script>
function canvaConnectorCopyToken() {
  var token = document.getElementById('canva-new-token');
  if (!token) return;
  var value = token.innerText || token.textContent || '';
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(value).then(function () {
      alert('Token copied. Return to Canva and paste it in the Connector token field.');
    }, function () {
      window.prompt('Copy this token, then paste it in Canva:', value);
    });
  } else {
    window.prompt('Copy this token, then paste it in Canva:', value);
  }
}
</script>
