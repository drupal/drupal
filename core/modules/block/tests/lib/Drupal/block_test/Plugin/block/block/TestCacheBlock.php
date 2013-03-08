<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\block\block\TestCacheBlock.
 */

namespace Drupal\block_test\Plugin\block\block;

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
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    return array(
      '#children' => state()->get('block_test.content'),
    );
  }

}
