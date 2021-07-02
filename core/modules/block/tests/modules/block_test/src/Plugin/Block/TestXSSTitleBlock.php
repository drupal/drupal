<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to test XSS in title.
 *
 * @Block(
 *   id = "test_xss_title",
 *   admin_label = "<script>alert('XSS subject');</script>"
 * )
 */
class TestXSSTitleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [];
  }

}
