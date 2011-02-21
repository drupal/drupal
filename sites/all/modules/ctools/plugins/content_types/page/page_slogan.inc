<?php
// $Id: page_slogan.inc,v 1.3 2010/09/07 09:02:51 sdboyer Exp $

/**
 * @file
 * Plugin to handle the 'page_slogan' content type which allows the
 * slogan of the site to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Site slogan'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('Add the slogan trail as content.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
);

/**
 * Output function for the 'page_slogan' content type.
 *
 * Outputs the slogan for the current page.
 */
function ctools_page_slogan_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = (theme_get_setting('toggle_slogan') ? filter_xss_admin(variable_get('site_slogan', '')) : '');

  return $block;
}
