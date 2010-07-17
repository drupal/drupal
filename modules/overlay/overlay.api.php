<?php
// $Id$

/**
 * @file
 * Hooks provided by Overlay module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to act when an overlay parent window is initialized.
 *
 * The parent window is initialized when a page is displayed in which the
 * overlay might be required to be displayed, so modules can act here if they
 * need to take action to accomodate the possibility of the overlay appearing
 * within a Drupal page.
 */
function hook_overlay_parent_initialize() {
  // Add our custom JavaScript.
  drupal_add_js(drupal_get_path('module', 'hook') . '/hook-overlay.js');
}

/**
 * Allow modules to act when an overlay child window is initialized.
 *
 * The child window is initialized when a page is displayed from within the
 * overlay, so modules can act here if they need to take action to work from
 * within the confines of the overlay.
 */
function hook_overlay_child_initialize() {
  // Add our custom JavaScript.
  drupal_add_js(drupal_get_path('module', 'hook') . '/hook-overlay-child.js');
}

/**
 * @} End of "addtogroup hooks".
 */
