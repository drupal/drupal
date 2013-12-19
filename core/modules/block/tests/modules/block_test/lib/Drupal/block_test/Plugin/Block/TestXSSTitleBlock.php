<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestXSSTitleBlock.
 */

namespace Drupal\block_test\Plugin\Block;

/**
 * Provides a block to test XSS in title.
 *
 * @Block(
 *   id = "test_xss_title",
 *   admin_label = "<script>alert('XSS subject');</script>"
 * )
 */
class TestXSSTitleBlock extends TestCacheBlock {

  /**
   * {@inheritdoc}
   *
   * Sets a different caching strategy for testing purposes.
   */
  public function defaultConfiguration() {
    return array(
      'cache' => DRUPAL_NO_CACHE,
    );
  }

}
