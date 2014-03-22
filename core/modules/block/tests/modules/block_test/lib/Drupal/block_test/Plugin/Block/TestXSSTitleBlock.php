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
}
