<?php

/**
 * @file
 * Post-update functions for Datetime Range module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function datetime_range_removed_post_updates(): array {
  return [
    'datetime_range_post_update_translatable_separator' => '9.0.0',
    'datetime_range_post_update_views_string_plugin_id' => '9.0.0',
    'datetime_range_post_update_from_to_configuration' => '11.0.0',
  ];

}
