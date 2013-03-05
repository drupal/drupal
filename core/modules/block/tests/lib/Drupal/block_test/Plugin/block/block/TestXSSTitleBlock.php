<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\block\block\TestXSSTitleBlock.
 */

namespace Drupal\block_test\Plugin\block\block;

use Drupal\Core\Annotation\Plugin;

/**
 * Provides a block to test XSS in title.
 *
 * @Plugin(
 *   id = "test_xss_title",
 *   admin_label = "<script>alert('XSS subject');</script>",
 *   module = "block_test"
 * )
 */
class TestXSSTitleBlock extends TestCacheBlock {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   *
   * Sets a different caching strategy for testing purposes.
   */
  public function settings() {
    return array(
      'cache' => DRUPAL_NO_CACHE,
    );
  }

}
