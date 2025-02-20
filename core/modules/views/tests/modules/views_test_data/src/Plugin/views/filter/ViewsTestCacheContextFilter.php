<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Provides a test filter plugin with a custom cache context.
 */
#[ViewsFilter("views_test_test_cache_context")]
class ViewsTestCacheContextFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->value = \Drupal::state()->get('views_test_cache_context', 'George');

    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();

    $cache_contexts[] = 'views_test_cache_context';
    return $cache_contexts;
  }

}
