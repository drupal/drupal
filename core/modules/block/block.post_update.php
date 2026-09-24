<?php

/**
 * @file
 * Post update functions for Block.
 */

/**
 * Implements hook_removed_post_updates().
 */
function block_removed_post_updates(): array {
  return [
    'block_post_update_disable_blocks_with_missing_contexts' => '9.0.0',
    'block_post_update_disabled_region_update' => '9.0.0',
    'block_post_update_fix_negate_in_conditions' => '9.0.0',
    'block_post_update_replace_node_type_condition' => '10.0.0',
    'block_post_update_make_weight_integer' => '12.0.0',
    'block_post_update_set_menu_block_depth_to_null_if_zero' => '12.0.0',
  ];
}

/**
 * Delete blocks placed using the removed Syndicate block plugin.
 */
function block_post_update_remove_syndicate_blocks(): void {
  $storage = \Drupal::entityTypeManager()->getStorage('block');
  $blocks = $storage->loadByProperties([
    'plugin' => 'node_syndicate_block',
    'settings.provider' => 'node',
  ]);
  if ($blocks) {
    $storage->delete($blocks);
  }
}
