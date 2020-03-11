<?php

/**
 * @file
 * Post update functions for Search module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function search_removed_post_updates() {
  return [
    'search_post_update_block_page' => '9.0.0',
  ];
}
