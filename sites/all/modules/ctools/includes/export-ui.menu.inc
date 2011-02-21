<?php
// $Id: export-ui.menu.inc,v 1.4 2011/01/01 01:14:24 merlinofchaos Exp $

/**
 * Delegated implementation of hook_menu().
 */
function ctools_export_ui_menu(&$items) {
  ctools_include('export-ui');

  // If a menu rebuild is triggered because of module enable/disable,
  // this might be out of date. Reset the cache.
  ctools_include('plugins');
  ctools_get_plugins_reset();

  foreach (ctools_get_export_uis() as $plugin) {
    // We also need to make sure that the module hasn't been disabled. During
    // the disable process, the module's plugins still still appear.
    if ($plugin['has menu'] && module_exists($plugin['module'])) {
      $handler = ctools_export_ui_get_handler($plugin);
      if ($handler) {
        $handler->hook_menu($items);
      }
    }
  }
}
