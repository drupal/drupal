<?php

/**
 * @file
 * Post update functions for Block.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function block_removed_post_updates() {
  return [
    'block_post_update_disable_blocks_with_missing_contexts' => '9.0.0',
    'block_post_update_disabled_region_update' => '9.0.0',
    'block_post_update_fix_negate_in_conditions' => '9.0.0',
    'block_post_update_replace_node_type_condition' => '10.0.0',
  ];
}

/**
 * Add 'base_route_title' setting for page title blocks.
 */
function block_post_update_add_base_route_title_page_title(&$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block) {
      if ($block->get('plugin') === 'page_title_block') {
        $settings = $block->get('settings');
        $settings['base_route_title'] = $block->get('theme') === 'claro';
        $block->set('settings', $settings);
        return TRUE;
      }
      return FALSE;
    });
}
