<?php

/**
 * @file
 * Post update functions for the comment module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function comment_removed_post_updates() {
  return [
    'comment_post_update_enable_comment_admin_view' => '9.0.0',
    'comment_post_update_add_ip_address_setting' => '9.0.0',
  ];
}
