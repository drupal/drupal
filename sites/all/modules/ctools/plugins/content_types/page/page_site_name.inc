<?php
// $Id: page_site_name.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to handle the 'page_site_name' content type which allows the
 * site_name of the site to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Site name'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('The name of the site.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
);

/**
 * Output function for the 'page_site_name' content type.
 *
 * Outputs the site_name for the current page.
 */
function ctools_page_site_name_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = filter_xss_admin(variable_get('site_name', 'Drupal'));

  return $block;
}
