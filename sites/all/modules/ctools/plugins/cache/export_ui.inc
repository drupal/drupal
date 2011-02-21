<?php
// $Id: export_ui.inc,v 1.1 2010/10/11 22:18:24 sdboyer Exp $

/**
 * @file
 * A caching mechanism for use with subsystems that use the export ui.
 */

$plugin = array(
  // cache plugins are the rare plugin types that have no real UI but
  // we're providing a title just in case.
  'title' => t('Export UI wizard cache'),
  'cache get' => 'ctools_cache_export_ui_cache_get',
  'cache set' => 'ctools_cache_export_ui_cache_set',
  // Some operations use a 'finalize' but that really just means set
  // for us, since we're not using temporary storage for subsystems.
  'cache finalize' => 'ctools_cache_export_ui_cache_set',
);

function ctools_cache_export_ui_cache_get($plugin_name, $key) {
  ctools_include('export-ui');
  $plugin = ctools_get_export_ui($plugin_name);
  $handler = ctools_export_ui_get_handler($plugin);
  if ($handler) {
    $item = $handler->edit_cache_get($key);
    if (!$item) {
      $item = ctools_export_crud_load($handler->plugin['schema'], $key);
    }
    return $item;
  }
}

function ctools_cache_export_ui_cache_set($plugin_name, $key, $item) {
  ctools_include('export-ui');
  $plugin = ctools_get_export_ui($plugin_name);
  $handler = ctools_export_ui_get_handler($plugin);
  if ($handler) {
    return $handler->edit_cache_set_key($item, $key);
  }
}
