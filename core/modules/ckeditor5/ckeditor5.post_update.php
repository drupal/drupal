<?php

/**
 * @file
 * Post update functions for CKEditor 5.
 */

/**
 * Implements hook_removed_post_updates().
 */
function ckeditor5_removed_post_updates() {
  return [
    'ckeditor5_post_update_alignment_buttons' => '10.0.0',
  ];
}
