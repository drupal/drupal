<?php

/**
 * @file
 * Post update functions for the Menu link content module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function menu_link_content_removed_post_updates(): array {
  return [
    'menu_link_content_post_update_make_menu_link_content_revisionable' => '9.0.0',
  ];
}
