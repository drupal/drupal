<?php

/**
 * @file
 * Functions to support theming in the Stark theme.
 */

/**
 * Implements hook_preprocess_HOOK() for html.tpl.php.
 *
 * @todo Based on outcome of http://drupal.org/node/1471382, revise this
 *   technique to use conditional classes vs. conditional stylesheets.
 */
function stark_preprocess_html(&$variables) {
  // Add conditional CSS for IE8 and below.
  drupal_add_css(path_to_theme() . '/css/ie.css', array('group' => CSS_THEME, 'browsers' => array('IE' => 'lte IE 8', '!IE' => FALSE), 'weight' => 999, 'preprocess' => FALSE));
}
