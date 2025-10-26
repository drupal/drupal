<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheOptionalInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block implementing CacheOptionalInterface to test its caching.
 */
#[Block(
  id: "test_cache_optional",
  admin_label: new TranslatableMarkup("Test block cache optional")
)]
class TestCacheOptionalBlock extends BlockBase implements CacheOptionalInterface {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
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
  public function getCacheContexts(): array {
    return \Drupal::state()->get('block_test.cache_contexts', parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return \Drupal::state()->get('block_test.cache_max_age', parent::getCacheMaxAge());
  }

}
