<?php

/**
 * @file
 * Post update functions for CKEditor 5.
 */

// cspell:ignore multiblock

/**
 * Implements hook_removed_post_updates().
 */
function ckeditor5_removed_post_updates() {
  return [
    'ckeditor5_post_update_alignment_buttons' => '10.0.0',
    'ckeditor5_post_update_image_toolbar_item' => '11.0.0',
    'ckeditor5_post_update_plugins_settings_export_order' => '11.0.0',
    'ckeditor5_post_update_code_block' => '11.0.0',
    'ckeditor5_post_update_list_multiblock' => '11.0.0',
    'ckeditor5_post_update_list_start_reversed' => '11.0.0',
  ];
}
