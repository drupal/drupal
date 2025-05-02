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
function block_removed_post_updates(): array {
  return [
    'block_post_update_disable_blocks_with_missing_contexts' => '9.0.0',
    'block_post_update_disabled_region_update' => '9.0.0',
    'block_post_update_fix_negate_in_conditions' => '9.0.0',
    'block_post_update_replace_node_type_condition' => '10.0.0',
  ];
}

/**
 * Ensures that all block weights are integers.
 */
function block_post_update_make_weight_integer(array &$sandbox = []): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block): bool {
      $weight = $block->getWeight();
      if (!is_int($weight)) {
        $block->setWeight($weight);
        return TRUE;
      }
      return FALSE;
    });
}

/**
 * Updates the `depth` setting to NULL if it is 0 in any menu blocks.
 */
function block_post_update_set_menu_block_depth_to_null_if_zero(array &$sandbox = []): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block): bool {
      if ($block->getPlugin()->getBaseId() === 'system_menu_block') {
        $settings = $block->get('settings');
        // Use `empty()` to account for either integer 0, or '0'.
        if (empty($settings['depth'])) {
          $settings['depth'] = NULL;
        }
        $block->set('settings', $settings);
        return TRUE;
      }
      return FALSE;
    });
}
