<?php

/**
 * @file
 * Post update functions for Media Library.
 */

/**
 * Implements hook_removed_post_updates().
 */
function media_library_removed_post_updates() {
  return [
    'media_library_post_update_display_modes' => '9.0.0',
    'media_library_post_update_table_display' => '9.0.0',
    'media_library_post_update_add_media_library_image_style' => '9.0.0',
    'media_library_post_update_add_status_extra_filter' => '9.0.0',
    'media_library_post_update_add_buttons_to_page_view' => '9.0.0',
    'media_library_post_update_update_8001_checkbox_classes' => '9.0.0',
    'media_library_post_update_default_administrative_list_to_table_display' => '9.0.0',
    'media_library_post_update_add_langcode_filters' => '9.0.0',
  ];
}
