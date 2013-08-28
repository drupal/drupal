<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestHtmlIdBlock.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block to test HTML IDs.
 *
 * @Block(
 *   id = "test_html_id",
 *   admin_label = @Translation("Test block html id")
 * )
 */
class TestHtmlIdBlock extends TestCacheBlock {
}
