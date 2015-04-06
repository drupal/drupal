<?php

/**
 * @file
 * Contains \Drupal\views_test_data\Cache\ViewsTestCacheContext.
 */

namespace Drupal\views_test_data\Cache;

use Drupal\Core\Cache\CacheContextInterface;

/**
 * Test cache context which uses a dynamic context coming from state.
 */
class ViewsTestCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Views test cache context');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return \Drupal::state()->get('views_test_cache_context', 'George');
  }

}
