<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestCacheBlock.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\block\BlockBase;

/**
 * Provides a block to test caching.
 *
 * @Block(
 *   id = "test_cache",
 *   admin_label = @Translation("Test block caching")
 * )
 */
class TestCacheBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Sets a different caching strategy for testing purposes.
   */
  public function defaultConfiguration() {
    return array(
      'cache' => DRUPAL_CACHE_PER_ROLE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#children' => \Drupal::state()->get('block_test.content'),
    );
  }

}
