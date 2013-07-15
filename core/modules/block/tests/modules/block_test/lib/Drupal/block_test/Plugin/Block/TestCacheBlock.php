<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestCacheBlock.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block to test caching.
 *
 * @Plugin(
 *   id = "test_cache",
 *   admin_label = @Translation("Test block caching"),
 *   module = "block_test"
 * )
 */
class TestCacheBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   *
   * Sets a different caching strategy for testing purposes.
   */
  public function settings() {
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
