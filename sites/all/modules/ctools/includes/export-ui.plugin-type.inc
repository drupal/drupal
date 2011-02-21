<?php
// $Id: export-ui.plugin-type.inc,v 1.1 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Contains plugin type registration information for the context tool.
 *
 * Don't actually need to declare anything for these plugin types right now,
 * apart from the fact that they exist. So, an empty array.
 */

function ctools_export_ui_plugin_type(&$items) {
  $items['export_ui'] = array(
    'process' => array(
      'function' => 'ctools_export_ui_process',
      'file' => 'export-ui.inc',
      'path' => drupal_get_path('module', 'ctools') . '/includes',
    ),
    'classes' => array('handler'),
  );
}