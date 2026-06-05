<?php
defined('CANVA_CONNECTOR_BOOTSTRAP') or define('CANVA_CONNECTOR_BOOTSTRAP', true);

if (ob_get_level() === 0) {
  ob_start();
}

if (!defined('PHPWG_ROOT_PATH')) {
  define('PHPWG_ROOT_PATH', dirname(__FILE__, 4) . '/');
}

include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

define('CANVA_CONNECTOR_ORIGIN', 'https://app-aahaaekscy8.canva-apps.com');
define('CANVA_CONNECTOR_TOKEN_FILE', PHPWG_ROOT_PATH . '_data/canva_connector_tokens.json');
define('CANVA_CONNECTOR_CONFIG_FILE', PHPWG_ROOT_PATH . '_data/canva_connector_config.json');
define('CANVA_CONNECTOR_MEDIA_SECRET_FILE', PHPWG_ROOT_PATH . '_data/canva_connector_media_secret');
define('CANVA_CONNECTOR_CACHE_DIR', PHPWG_ROOT_PATH . '_data/canva_connector_cache');
define('CANVA_CONNECTOR_LAST_USED_THROTTLE_SECONDS', 300);
define('CANVA_CONNECTOR_SIGNED_URL_TTL_SECONDS', 3600);

function canva_connector_default_config(): array
{
  return array(
    'asset_max_dimension' => 2048,
    'asset_quality' => 82,
    'preview_max_dimension' => 1280,
    'preview_quality' => 76,
    'thumb_max_dimension' => 360,
    'thumb_quality' => 72,
    'convert_png_to_jpeg' => 'auto',
  );
}

function canva_connector_clamp_int($value, int $min, int $max, int $fallback): int
{
  $value = is_numeric($value) ? (int) $value : $fallback;
  return min($max, max($min, $value));
}

function canva_connector_normalize_config(array $config): array
{
  $defaults = canva_connector_default_config();
  $convert = (string) ($config['convert_png_to_jpeg'] ?? $defaults['convert_png_to_jpeg']);
  if (!in_array($convert, array('auto', 'never', 'always'), true)) {
    $convert = $defaults['convert_png_to_jpeg'];
  }

  return array(
    'asset_max_dimension' => canva_connector_clamp_int($config['asset_max_dimension'] ?? null, 800, 6000, $defaults['asset_max_dimension']),
    'asset_quality' => canva_connector_clamp_int($config['asset_quality'] ?? null, 45, 95, $defaults['asset_quality']),
    'preview_max_dimension' => canva_connector_clamp_int($config['preview_max_dimension'] ?? null, 320, 2400, $defaults['preview_max_dimension']),
    'preview_quality' => canva_connector_clamp_int($config['preview_quality'] ?? null, 45, 95, $defaults['preview_quality']),
    'thumb_max_dimension' => canva_connector_clamp_int($config['thumb_max_dimension'] ?? null, 120, 900, $defaults['thumb_max_dimension']),
    'thumb_quality' => canva_connector_clamp_int($config['thumb_quality'] ?? null, 45, 90, $defaults['thumb_quality']),
    'convert_png_to_jpeg' => $convert,
  );
}

function canva_connector_read_config(): array
{
  if (!is_file(CANVA_CONNECTOR_CONFIG_FILE)) {
    return canva_connector_default_config();
  }

  $raw = file_get_contents(CANVA_CONNECTOR_CONFIG_FILE);
  $config = json_decode($raw ?: '{}', true);
  return canva_connector_normalize_config(is_array($config) ? $config : array());
}

function canva_connector_write_config(array $config): void
{
  $dir = dirname(CANVA_CONNECTOR_CONFIG_FILE);
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  file_put_contents(
    CANVA_CONNECTOR_CONFIG_FILE,
    json_encode(canva_connector_normalize_config($config), JSON_PRETTY_PRINT)
  );
}

function canva_connector_json($payload, int $status = 200): void
{
  while (ob_get_level() > 0) {
    ob_end_clean();
  }

  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function canva_connector_cors(): void
{
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin === CANVA_CONNECTOR_ORIGIN) {
    header('Access-Control-Allow-Origin: ' . CANVA_CONNECTOR_ORIGIN);
    header('Vary: Origin');
  }

  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Authorization, Content-Type');
  header('Access-Control-Max-Age: 86400');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function canva_connector_require_admin(): void
{
  global $user;

  $status = $user['status'] ?? 'guest';
  if (!in_array($status, array('webmaster', 'admin'), true)) {
    http_response_code(403);
    echo 'Only Piwigo administrators can manage Canva Connector tokens.';
    exit;
  }
}

function canva_connector_csrf_token(): string
{
  if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
  }

  if (empty($_SESSION['canva_connector_csrf'])) {
    $_SESSION['canva_connector_csrf'] = bin2hex(random_bytes(16));
  }

  return (string) $_SESSION['canva_connector_csrf'];
}

function canva_connector_verify_csrf(): void
{
  $posted = (string) ($_POST['csrf_token'] ?? '');
  if (!$posted || !hash_equals(canva_connector_csrf_token(), $posted)) {
    http_response_code(400);
    echo 'Invalid request token.';
    exit;
  }
}

function canva_connector_base_url(): string
{
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  $pos = strpos($script, '/plugins/canva_connector/');
  $path = $pos === false ? '/' : substr($script, 0, $pos + 1);

  return rtrim($scheme . '://' . $host . $path, '/');
}

function canva_connector_read_tokens(): array
{
  if (!is_file(CANVA_CONNECTOR_TOKEN_FILE)) {
    return array();
  }

  $raw = file_get_contents(CANVA_CONNECTOR_TOKEN_FILE);
  $tokens = json_decode($raw ?: '[]', true);
  return is_array($tokens) ? $tokens : array();
}

function canva_connector_write_tokens(array $tokens): void
{
  $dir = dirname(CANVA_CONNECTOR_TOKEN_FILE);
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  file_put_contents(
    CANVA_CONNECTOR_TOKEN_FILE,
    json_encode(array_values($tokens), JSON_PRETTY_PRINT)
  );
}

function canva_connector_generate_token(string $label, int $user_id): string
{
  $plain = 'pwgcc_' . bin2hex(random_bytes(32));
  $tokens = canva_connector_read_tokens();
  $tokens[] = array(
    'id' => bin2hex(random_bytes(8)),
    'label' => $label ?: 'Canva',
    'hash' => hash('sha256', $plain),
    'created_by' => $user_id,
    'created_at' => gmdate('c'),
    'revoked_at' => null,
    'last_used_at' => null,
  );

  canva_connector_write_tokens($tokens);
  return $plain;
}

function canva_connector_revoke_token(string $id): void
{
  $tokens = canva_connector_read_tokens();
  foreach ($tokens as &$token) {
    if (($token['id'] ?? '') === $id) {
      $token['revoked_at'] = gmdate('c');
    }
  }
  unset($token);

  canva_connector_write_tokens($tokens);
}

function canva_connector_current_token(bool $touch_last_used = true): array
{
  $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!$header && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
  }

  $token = '';
  if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
    $token = trim($matches[1]);
  }

  if (!$token) {
    canva_connector_json(array('error' => 'Missing connector token'), 401);
  }

  $hash = hash('sha256', $token);
  $tokens = canva_connector_read_tokens();

  foreach ($tokens as $index => $record) {
    if (!empty($record['revoked_at'])) {
      continue;
    }

    if (hash_equals((string) ($record['hash'] ?? ''), $hash)) {
      if ($touch_last_used) {
        $last_used_at = strtotime((string) ($record['last_used_at'] ?? '')) ?: 0;
        if ($last_used_at < time() - CANVA_CONNECTOR_LAST_USED_THROTTLE_SECONDS) {
          $tokens[$index]['last_used_at'] = gmdate('c');
          canva_connector_write_tokens($tokens);
        }
      }
      return $record;
    }
  }

  canva_connector_json(array('error' => 'Invalid connector token'), 401);
}

function canva_connector_require_token(bool $touch_last_used = true): array
{
  canva_connector_cors();
  return canva_connector_current_token($touch_last_used);
}

function canva_connector_clean_text($value): string
{
  return html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
}

function canva_connector_media_url(int $photo_id, string $variant = 'full'): string
{
  $base = canva_connector_base_url();
  return $base . '/plugins/canva_connector/api/media.php?photoId=' . $photo_id . '&variant=' . rawurlencode($variant);
}

function canva_connector_media_secret(): string
{
  if (is_file(CANVA_CONNECTOR_MEDIA_SECRET_FILE)) {
    $secret = trim((string) file_get_contents(CANVA_CONNECTOR_MEDIA_SECRET_FILE));
    if ($secret !== '') {
      return $secret;
    }
  }

  $dir = dirname(CANVA_CONNECTOR_MEDIA_SECRET_FILE);
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  $secret = bin2hex(random_bytes(32));
  file_put_contents(CANVA_CONNECTOR_MEDIA_SECRET_FILE, $secret);
  return $secret;
}

function canva_connector_media_signature(int $photo_id, string $variant, int $expires): string
{
  return hash_hmac(
    'sha256',
    $photo_id . '|' . $variant . '|' . $expires,
    canva_connector_media_secret()
  );
}

function canva_connector_signed_media_url(int $photo_id, string $variant = 'asset', $expires = null): string
{
  $expires = $expires ?: time() + CANVA_CONNECTOR_SIGNED_URL_TTL_SECONDS;
  return canva_connector_media_url($photo_id, $variant)
    . '&expires=' . $expires
    . '&sig=' . rawurlencode(canva_connector_media_signature($photo_id, $variant, $expires));
}

function canva_connector_has_valid_media_signature(int $photo_id, string $variant): bool
{
  $expires = isset($_GET['expires']) ? (int) $_GET['expires'] : 0;
  $sig = (string) ($_GET['sig'] ?? '');
  if ($photo_id <= 0 || $expires < time() || $sig === '') {
    return false;
  }

  return hash_equals(canva_connector_media_signature($photo_id, $variant, $expires), $sig);
}

function canva_connector_cache_dir(): string
{
  if (!is_dir(CANVA_CONNECTOR_CACHE_DIR)) {
    mkdir(CANVA_CONNECTOR_CACHE_DIR, 0755, true);
  }

  return CANVA_CONNECTOR_CACHE_DIR;
}

function canva_connector_clean_output_buffer(): void
{
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
}
