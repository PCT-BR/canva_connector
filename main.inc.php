<?php
/*
Plugin Name: Canva Connector
Version: 1.0.0
Description: Connects a Piwigo gallery to the Canva Piwigo Media app without sharing Piwigo API keys with a central backend.
Plugin URI: https://github.com/PCT-BR/Canvaconnector-for-piwigo
Author: PCT-BR
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

define('CANVA_CONNECTOR_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');

add_event_handler('get_admin_plugin_menu_links', 'canva_connector_admin_menu');

function canva_connector_admin_menu($menu)
{
  $url = get_root_url() . 'plugins/' . basename(dirname(__FILE__)) . '/connect.php';
  if (function_exists('get_admin_plugin_menu_link')) {
    $url = get_admin_plugin_menu_link(dirname(__FILE__) . '/admin.php');
  }

  $menu[] = array(
    'NAME' => 'Canva Connector - Tokens',
    'URL'  => $url . '&amp;tab=tokens',
  );

  $menu[] = array(
    'NAME' => 'Canva Connector - Media settings',
    'URL'  => $url . '&amp;tab=settings',
  );

  return $menu;
}
