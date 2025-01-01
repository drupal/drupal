<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block to test caching.
 */
#[Block(
  id: "test_cache",
  admin_label: new TranslatableMarkup("Test block caching")
)]
class TestCacheBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = \Drupal::keyValue('block_test')->get('content');

    $build = [];
    if (!empty($content)) {
      $build['#markup'] = $content;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return \Drupal::state()->get('block_test.cache_contexts', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return \Drupal::state()->get('block_test.cache_max_age', parent::getCacheMaxAge());
  }

}
