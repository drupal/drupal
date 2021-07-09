<?php

/**
 * @file
 * Post update functions for forum module.
 */

/**
 * Removes the unused 'properties' key from forum blocks.
 */
function forum_post_update_remove_properties_key() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('block.block.') as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);

    if (in_array($block->get('plugin'), ['forum_active_block', 'forum_new_block'], TRUE)) {
      $block->clear('settings.properties');
      $block->save(TRUE);
    }
  }
}
