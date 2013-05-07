<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\Block\TestHtmlIdBlock.
 */

namespace Drupal\block_test\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block to test HTML IDs.
 *
 * @Plugin(
 *   id = "test_html_id",
 *   admin_label = @Translation("Test block html id"),
 *   module = "block_test"
 * )
 */
class TestHtmlIdBlock extends TestCacheBlock {
}
