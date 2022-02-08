<?php

/**
 * @file
 * Contains post update hooks for the text module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function text_removed_post_updates() {
  return [
    'text_post_update_add_required_summary_flag' => '9.0.0',
    'text_post_update_add_required_summary_flag_form_display' => '10.0.0',
  ];
}
