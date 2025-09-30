<?php

/**
 * @file
 * Post update functions for Content Block.
 */

use Drupal\block\BlockConfigUpdater;
use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewsConfigUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function block_content_removed_post_updates(): array {
  return [
    'block_content_post_update_add_views_reusable_filter' => '9.0.0',
    'block_content_post_update_entity_changed_constraint' => '11.0.0',
    'block_content_post_update_move_custom_block_library' => '11.0.0',
    'block_content_post_update_block_library_view_permission' => '11.0.0',
    'block_content_post_update_sort_permissions' => '11.0.0',
    'block_content_post_update_revision_type' => '11.0.0',
  ];
}

/**
 * Remove deprecated status and info keys from block_content blocks.
 */
function block_content_post_update_remove_block_content_status_info_keys(array &$sandbox = []): void {
  /** @var \Drupal\block\BlockConfigUpdater $blockConfigUpdater */
  $blockConfigUpdater = \Drupal::service(BlockConfigUpdater::class);
  $blockConfigUpdater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block) use ($blockConfigUpdater): bool {
      return $blockConfigUpdater->needsInfoStatusSettingsRemoved($block);
    });
}

/**
 * Remove block_content_listing_empty from views.
 */
function block_content_post_update_remove_block_content_listing_empty(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  $view_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($view_config_updater): bool {
    return $view_config_updater->needsBlockContentListingEmptyUpdate($view);
  });
}
