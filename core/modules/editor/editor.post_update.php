<?php

/**
 * @file
 * Post update functions for Editor.
 */

/**
 * Implements hook_removed_post_updates().
 */
function editor_removed_post_updates(): array {
  return [
    'editor_post_update_clear_cache_for_file_reference_filter' => '9.0.0',
    'editor_post_update_image_lazy_load' => '11.0.0',
    'editor_post_update_sanitize_image_upload_settings' => '11.0.0',
  ];
}
