<?php

/**
 * @file
 * Post update functions for the Navigation module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function navigation_removed_post_updates(): array {
  return [
    'navigation_post_update_update_permissions' => '12.0.0',
    'navigation_post_update_set_logo_dimensions_default' => '12.0.0',
    'navigation_post_update_navigation_user_links_menu' => '12.0.0',
    'navigation_post_update_uninstall_navigation_top_bar' => '12.0.0',
    'navigation_post_update_refresh_tempstore_repository' => '12.0.0',
  ];
}
