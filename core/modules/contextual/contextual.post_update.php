<?php

/**
 * @file
 * Post update functions for Contextual Links.
 */

/**
 * Implements hook_removed_post_updates().
 */
function contextual_removed_post_updates() {
  return [
    'contextual_post_update_fixed_endpoint_and_markup' => '9.0.0',
  ];
}
