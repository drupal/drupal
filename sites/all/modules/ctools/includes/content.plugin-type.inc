<?php
// $Id: content.plugin-type.inc,v 1.1 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Contains plugin type registration information for the content tool.
 */

function ctools_content_plugin_type(&$items) {
  $items['content_types'] = array(
    'cache' => FALSE,
    'process' => array(
      'function' => 'ctools_content_process',
      'file' => 'export-ui.inc',
      'path' => drupal_get_path('module', 'ctools') . '/includes',
    ),
  );
}