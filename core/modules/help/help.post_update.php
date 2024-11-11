<?php

/**
 * @file
 * Post update functions for the Help module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function help_removed_post_updates(): array {
  return [
    'help_post_update_help_topics_search' => '11.0.0',
    'help_post_update_help_topics_uninstall' => '11.0.0',
    'help_post_update_add_permissions_to_roles' => '11.0.0',
  ];
}
