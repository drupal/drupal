<?php

/**
 * @file
 * Post update functions for Language module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function language_removed_post_updates() {
  return [
    'language_post_update_language_select_widget' => '9.0.0',
  ];
}
