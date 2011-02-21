<?php
// $Id: utility.inc,v 1.3 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Contains general utility functions for CTools that do not need to be
 * in the module file.
 *
 * In particular, things that are only needed during hook_menu() and
 * hook_theme() are placed here.
 */

/**
 * Provide a hook passthrough to included files.
 *
 * To organize things neatly, each CTools tool gets its own toolname.$type.inc
 * file. If it exists, it's loaded and ctools_$tool_$type() is executed.
 * To save time we pass the $items array in so we don't need to do array
 * addition. It modifies the array by reference and doesn't need to return it.
 */
function _ctools_passthrough(&$items, $type = 'theme') {
  $files = drupal_system_listing('/\.' . $type . '\.inc$/', drupal_get_path('module', 'ctools') . '/includes', 'name', 0);
  foreach ($files as $file) {
    require_once DRUPAL_ROOT . '/' . $file->uri;
    list($tool) = explode('.', $file->name, 2);

    $function = 'ctools_' . str_replace ('-', '_', $tool) . '_' . str_replace('-', '_', $type);
    if (function_exists($function)) {
      $function($items);
    }
  }
}

/**
 * Implementation of hook_theme_registry_alter()
 */
function ctools_theme_registry_alter(&$registry) {
  // Move this one last last last so it can catch changes made by modules and themes.
  $key = array_search('ctools_preprocess_page', $registry['page']['preprocess functions']);
  if ($key) {
    unset($registry['page']['preprocess functions'][$key]);
  }
  $registry['page']['preprocess functions'][] = 'ctools_preprocess_page';
}

