<?php

/**
 * @file
 * Post update functions for Search module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\block\BlockInterface;

/**
 * Implements hook_removed_post_updates().
 */
function search_removed_post_updates(): array {
  return [
    'search_post_update_block_page' => '9.0.0',
    'search_post_update_reindex_after_diacritics_rule_change' => '10.0.0',
  ];
}

/**
 * Updates Search Blocks' without an explicit `page_id` from '' to NULL.
 */
function search_post_update_block_with_empty_page_id(&$sandbox = []): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'block', function (BlockInterface $block): bool {
    // Only update blocks using the search block plugin.
    // @see search_block_presave()
    if ($block->getPluginId() === 'search_form_block' && $block->get('settings')['page_id'] === '') {
      $settings = $block->get('settings');
      $settings['page_id'] = NULL;
      $block->set('settings', $settings);
      return TRUE;
    }
    return FALSE;
  });
}
