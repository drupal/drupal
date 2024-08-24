<?php

declare(strict_types=1);

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
