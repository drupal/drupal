<?php

/**
 * @file
 * Post-update functions for Locale module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function locale_removed_post_updates() {
  return [
    'locale_post_update_clear_cache_for_old_translations' => '9.0.0',
  ];
}
