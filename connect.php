<?php
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
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Canva Connector</title>
  <style>
    body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; max-width: 920px; margin: 40px auto; padding: 0 20px; color: #1f2933; }
    code, input { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .card { border: 1px solid #d9e2ec; border-radius: 8px; padding: 20px; margin: 18px 0; background: #fff; }
    .warning { border-color: #f7c948; background: #fffbea; }
    .token { background: #f0f4f8; border-radius: 6px; padding: 12px; overflow-wrap: anywhere; }
    .muted { color: #627d98; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #e4e7eb; padding: 10px; text-align: left; }
    button { border: 0; border-radius: 6px; padding: 9px 14px; background: #00c4cc; color: #061018; cursor: pointer; font-weight: 600; }
    button.secondary { background: #d9e2ec; }
    input { padding: 8px; border: 1px solid #bcccdc; border-radius: 6px; }
    input { width: min(100%, 420px); }
    ul { line-height: 1.55; }
  </style>
</head>
<body>
  <h1>Canva Connector</h1>
  <p>This connector lets the Canva Piwigo Media app access this Piwigo instance without sharing Piwigo API keys with a central backend.</p>

  <div class="card">
    <h2>Connection details</h2>
    <p>Piwigo URL to enter in Canva:</p>
    <p class="token"><?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>

  <?php if ($new_token): ?>
    <div class="card">
      <h2>Connection authorized</h2>
      <p>Copy this token into Canva now. It is shown only once.</p>
      <p id="new-token" class="token"><?php echo htmlspecialchars($new_token, ENT_QUOTES, 'UTF-8'); ?></p>
      <button type="button" onclick="copyToken()">Copy token</button>
      <p class="muted">After copying, return to Canva and paste it in the Connector token field.</p>
    </div>
  <?php endif; ?>

  <div class="card warning">
    <h2>Authorize Canva access</h2>
    <p>Canva Piwigo Media will be allowed to:</p>
    <ul>
      <li>list albums from this Piwigo instance</li>
      <li>read photo files that you insert into Canva</li>
      <li>upload Canva exports to the album you select in Canva</li>
    </ul>
    <p>Canva Piwigo Media will not receive your Piwigo username, password, or Piwigo API keys.</p>
    <p>You can revoke this token here at any time.</p>
    <form method="post">
      <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
      >
      <label>
        Token label
        <input name="label" value="Canva Piwigo Media" maxlength="80">
      </label>
      <button type="submit" name="authorize" value="1">Authorize and generate token</button>
    </form>
  </div>

  <div class="card">
    <h2>Existing tokens</h2>
    <table>
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
                  <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                  >
                  <button
                    class="secondary"
                    type="submit"
                    name="revoke"
                    value="<?php echo htmlspecialchars($token['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                  >
                    Revoke
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script>
    async function copyToken() {
      const token = document.getElementById('new-token')?.innerText || '';
      if (!token) return;
      try {
        await navigator.clipboard.writeText(token);
        alert('Token copied. Return to Canva and paste it in the Connector token field.');
      } catch (error) {
        window.prompt('Copy this token, then paste it in Canva:', token);
      }
    }
  </script>
</body>
</html>
