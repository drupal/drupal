<?php
// $Id: page_help.inc,v 1.3 2010/09/07 09:02:51 sdboyer Exp $

/**
 * @file
 * Plugin to handle the 'page_help' content type which allows the
 * help text of the current page to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Help'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('Add the help text of the current page as content.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
);

/**
 * Output function for the 'page_help' content type.
 *
 * Outputs the breadcrumb for the current page.
 */
function ctools_page_help_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = theme('help');

  return $block;
}

