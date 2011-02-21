<?php
// $Id: page_feed_icons.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to handle the 'page_feed_icons' content type which allows the
 * feed_icons statement of the site to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Feed icons'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('Add the site feed_icons statement as content.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
);

/**
 * Output function for the 'page_feed_icons' content type.
 *
 * Outputs the feed_icons statement for the site.
 */
function ctools_page_feed_icons_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = drupal_get_feeds();

  return $block;
}
