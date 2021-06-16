<?php

/**
 * @file
 * Post update functions for Block.
 */

/**
 * Implements hook_removed_post_updates().
 */
function block_removed_post_updates() {
  return [
    'block_post_update_disable_blocks_with_missing_contexts' => '9.0.0',
    'block_post_update_disabled_region_update' => '9.0.0',
    'block_post_update_fix_negate_in_conditions' => '9.0.0',
  ];
}
