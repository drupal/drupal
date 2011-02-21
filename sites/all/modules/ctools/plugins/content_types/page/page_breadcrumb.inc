<?php
// $Id: page_breadcrumb.inc,v 1.4 2010/12/31 21:20:57 merlinofchaos Exp $

/**
 * @file
 * Plugin to handle the 'page_breadcrumb' content type which allows the
 * breadcrumb trail of the current page to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Breadcrumb'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('Add the breadcrumb trail as content.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
);

/**
 * Output function for the 'page_breadcrumb' content type.
 *
 * Outputs the breadcrumb for the current page.
 */
function ctools_page_breadcrumb_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = theme('breadcrumb', array('breadcrumb' => drupal_get_breadcrumb()));

  return $block;
}
