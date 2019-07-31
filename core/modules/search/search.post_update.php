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
