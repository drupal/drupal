<?php

/**
 * @file
 * Post update functions for Search module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function search_removed_post_updates() {
  return [
    'search_post_update_block_page' => '9.0.0',
  ];
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
