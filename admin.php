<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

include_once(dirname(__FILE__) . '/include/bootstrap.php');
canva_connector_require_admin();

global $page, $template, $user;

$tab = (string) ($_GET['tab'] ?? 'tokens');
if (!in_array($tab, array('tokens', 'settings'), true)) {
  $tab = 'tokens';
}

$new_token = null;
$settings_saved = false;
$token_revoked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  canva_connector_verify_csrf();

  if (isset($_POST['authorize'])) {
    $label = trim((string) ($_POST['label'] ?? 'Canva Piwigo Media'));
    $new_token = canva_connector_generate_token($label, (int) ($user['id'] ?? 0));
    $tab = 'tokens';
  } elseif (isset($_POST['revoke'])) {
    canva_connector_revoke_token((string) $_POST['revoke']);
    $token_revoked = true;
    $tab = 'tokens';
  } elseif (isset($_POST['save_settings'])) {
    canva_connector_write_config(array(
      'asset_max_dimension' => $_POST['asset_max_dimension'] ?? null,
      'asset_quality' => $_POST['asset_quality'] ?? null,
      'preview_max_dimension' => $_POST['preview_max_dimension'] ?? null,
      'preview_quality' => $_POST['preview_quality'] ?? null,
      'thumb_max_dimension' => $_POST['thumb_max_dimension'] ?? null,
      'thumb_quality' => $_POST['thumb_quality'] ?? null,
      'convert_png_to_jpeg' => $_POST['convert_png_to_jpeg'] ?? null,
    ));
    $settings_saved = true;
    $tab = 'settings';
  }
}

$admin_base_url = function_exists('get_admin_plugin_menu_link')
  ? get_admin_plugin_menu_link(dirname(__FILE__) . '/admin.php')
  : canva_connector_base_url() . '/plugins/canva_connector/admin.php';

function canva_connector_admin_tab_url(string $base_url, string $tab): string
{
  $separator = strpos(html_entity_decode($base_url, ENT_QUOTES, 'UTF-8'), '?') === false ? '?' : '&';
  return $base_url . $separator . 'tab=' . rawurlencode($tab);
}

$config = canva_connector_read_config();
$tokens = canva_connector_read_tokens();
$csrf_token = canva_connector_csrf_token();
$tabs = array(
  array(
    'id' => 'tokens',
    'label' => 'Tokens',
    'url' => canva_connector_admin_tab_url($admin_base_url, 'tokens'),
    'active' => $tab === 'tokens',
  ),
  array(
    'id' => 'settings',
    'label' => 'Media settings',
    'url' => canva_connector_admin_tab_url($admin_base_url, 'settings'),
    'active' => $tab === 'settings',
  ),
);

$template->assign(array(
  'CANVA_ACTIVE_TAB' => $tab,
  'CANVA_TABS' => $tabs,
  'CANVA_BASE_URL' => canva_connector_base_url(),
  'CANVA_FORM_ACTION_TOKENS' => canva_connector_admin_tab_url($admin_base_url, 'tokens'),
  'CANVA_FORM_ACTION_SETTINGS' => canva_connector_admin_tab_url($admin_base_url, 'settings'),
  'CANVA_TOKENS_TEMPLATE' => dirname(__FILE__) . '/template/tokens.tpl',
  'CANVA_SETTINGS_TEMPLATE' => dirname(__FILE__) . '/template/settings.tpl',
  'CANVA_CSRF_TOKEN' => $csrf_token,
  'CANVA_NEW_TOKEN' => $new_token,
  'CANVA_TOKEN_REVOKED' => $token_revoked,
  'CANVA_SETTINGS_SAVED' => $settings_saved,
  'CANVA_TOKENS' => $tokens,
  'CANVA_CONFIG' => $config,
  'CANVA_PNG_POLICIES' => array(
    array('value' => 'auto', 'label' => 'Auto for thumbnails and previews'),
    array('value' => 'never', 'label' => 'Never convert PNG files'),
    array('value' => 'always', 'label' => 'Always convert PNG files to JPEG'),
  ),
));

$template->set_filename('canva_connector_admin', dirname(__FILE__) . '/template/admin.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'canva_connector_admin');
