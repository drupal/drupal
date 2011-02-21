<?php
// $Id: page_primary_links.inc,v 1.3 2010/12/31 21:21:39 merlinofchaos Exp $

/**
 * @file
 * Plugin to handle the 'page' content type which allows the standard page
 * template variables to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Primary navigation links'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('Add the primary_links (local tasks) as content.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
);

/**
 * Output function for the 'page_primary_links' content type.
 *
 * Outputs the primary_links (local tasks) of the current page.
 */
function ctools_page_primary_links_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = theme('links', array('links' => menu_main_menu(), 'attributes' => array('class' => 'links primary-links')));

  return $block;
}
