<?php

/**
 * @file
 * Post update functions for Search module.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Configures default search page for instantiated blocks.
 */
function search_post_update_block_page(&$sandbox = NULL) {
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    // Early exit when block module disabled.
    return;
  }
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block) {
      // Save search block to set default search page from plugin.
      return $block->getPluginId() === 'search_form_block';
    });
}

/**
 * Mark everything for reindexing after diacritics removal rule change.
 */
function search_post_update_reindex_after_diacritics_rule_change() {
  $search_page_repository = \Drupal::service('search.search_page_repository');
  foreach ($search_page_repository->getIndexableSearchPages() as $entity) {
    $entity->getPlugin()->markForReindex();
  }
  return t("Content has been marked for re-indexing for all active search pages. Searching will continue to work, but new content won't be indexed until all existing content has been re-indexed.");
}
