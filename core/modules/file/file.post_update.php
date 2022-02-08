<?php

/**
 * @file
 * Post update functions for File.
 */

/**
 * Implements hook_removed_post_updates().
 */
function file_removed_post_updates() {
  return [
    'file_post_update_add_txt_if_allows_insecure_extensions' => '10.0.0',
  ];
}
