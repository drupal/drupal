<?php
// $Id: content.theme.inc,v 1.4 2009/10/17 22:58:25 sdboyer Exp $

/**
 * @file
 * Contains theme registry and theme implementations for the content types.
 */

/**
 * Implements hook_theme to load all content plugins and pass thru if
 * necessary.
 */
function ctools_content_theme(&$theme) {
  ctools_include('content');

  $plugins = ctools_get_content_types();
  foreach ($plugins as $plugin) {
    if ($function = ctools_plugin_get_function($plugin, 'hook theme')) {
      $function($theme, $plugin);
    }
  }
}
