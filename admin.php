<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

include_once(dirname(__FILE__) . '/include/bootstrap.php');
canva_connector_require_admin();

global $user;

$new_token = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  canva_connector_verify_csrf();

  if (isset($_POST['authorize'])) {
    $label = trim($_POST['label'] ?? 'Canva Piwigo Media');
    $new_token = canva_connector_generate_token($label, (int) ($user['id'] ?? 0));
  } elseif (isset($_POST['revoke'])) {
    canva_connector_revoke_token((string) $_POST['revoke']);
  }
}

$tokens = canva_connector_read_tokens();
$base_url = canva_connector_base_url();
$csrf_token = canva_connector_csrf_token();
?>

<style>
  .canva-card { border: 1px solid #d9e2ec; border-radius: 8px; padding: 20px; margin: 18px 0; background: #fff; }
  .canva-card.warning { border-color: #f7c948; background: #fffbea; }
  .canva-token { background: #f0f4f8; border-radius: 6px; padding: 12px; overflow-wrap: anywhere; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  .canva-muted { color: #627d98; }
  #canva-tokens-table { width: 100%; border-collapse: collapse; }
  #canva-tokens-table th,
  #canva-tokens-table td { border-bottom: 1px solid #e4e7eb; padding: 10px; text-align: left; }
</style>

<div class="titrePage">
  <h2>Canva Connector</h2>
</div>

<p>This connector lets the Canva Piwigo Media app access this Piwigo instance without sharing Piwigo API keys with a central backend.</p>

<div class="canva-card">
  <h3>Connection details</h3>
  <p>Piwigo URL to enter in Canva:</p>
  <p class="canva-token"><?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<?php if ($new_token): ?>
  <div class="canva-card">
    <h3>Connection authorized</h3>
    <p>Copy this token into Canva now. It is shown only once.</p>
    <p id="canva-new-token" class="canva-token"><?php echo htmlspecialchars($new_token, ENT_QUOTES, 'UTF-8'); ?></p>
    <button type="button" class="button" onclick="canvaCopyToken()">Copy token</button>
    <p class="canva-muted">After copying, return to Canva and paste it in the Connector token field.</p>
  </div>
<?php endif; ?>

<div class="canva-card warning">
  <h3>Authorize Canva access</h3>
  <p>Canva Piwigo Media will be allowed to:</p>
  <ul>
    <li>list albums from this Piwigo instance</li>
    <li>read photo files that you insert into Canva</li>
    <li>upload Canva exports to the album you select in Canva</li>
  </ul>
  <p>Canva Piwigo Media will not receive your Piwigo username, password, or Piwigo API keys.</p>
  <p>You can revoke this token here at any time.</p>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <p>
      <label for="canva-token-label">Token label</label>
      <input id="canva-token-label" name="label" value="Canva Piwigo Media" maxlength="80">
    </p>
    <button type="submit" name="authorize" value="1" class="button">Authorize and generate token</button>
  </form>
</div>

<div class="canva-card">
  <h3>Existing tokens</h3>
  <table id="canva-tokens-table">
    <thead>
      <tr>
        <th>Label</th>
        <th>Created</th>
        <th>Last used</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tokens as $token): ?>
        <tr>
          <td><?php echo htmlspecialchars($token['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($token['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($token['last_used_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo empty($token['revoked_at']) ? 'Active' : 'Revoked'; ?></td>
          <td>
            <?php if (empty($token['revoked_at'])): ?>
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <button
                  class="button"
                  type="submit"
                  name="revoke"
                  value="<?php echo htmlspecialchars($token['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                >Revoke</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
async function canvaCopyToken() {
  const token = document.getElementById('canva-new-token')?.innerText || '';
  if (!token) return;
  try {
    await navigator.clipboard.writeText(token);
    alert('Token copied. Return to Canva and paste it in the Connector token field.');
  } catch (error) {
    window.prompt('Copy this token, then paste it in Canva:', token);
  }
}
</script>
