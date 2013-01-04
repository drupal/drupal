<?php

/**
 * @file
 * Contains \Drupal\block_test\Plugin\block\block\TestHtmlIdBlock.
 */

namespace Drupal\block_test\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a block to test HTML IDs.
 *
 * @Plugin(
 *   id = "test_html_id",
 *   subject = @Translation("Test block html id"),
 *   module = "block_test"
 * )
 */
class TestHtmlIdBlock extends TestCacheBlock {
}
