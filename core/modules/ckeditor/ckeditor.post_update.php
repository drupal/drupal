<?php

/**
 * @file
 * Post update functions for CKEditor.
 */

/**
 * Implements hook_removed_post_updates().
 */
function ckeditor_removed_post_updates() {
  return [
    'ckeditor_post_update_omit_settings_for_disabled_plugins' => '10.0.0',
  ];
}
