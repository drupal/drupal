<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the QueryArgsCacheContext service, for "per query args" caching.
 *
 * Cache context ID: 'url.query_args' (to vary by all query arguments).
 * Calculated cache context ID: 'url.query_args:%key', e.g.'url.query_args:foo'
 * (to vary by the 'foo' query argument).
 */
class QueryArgsCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Query arguments');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($query_arg = NULL) {
    if ($query_arg === NULL) {
      // All arguments requested. Use normalized query string to minimize
      // variations.
      $value = $this->requestStack->getCurrentRequest()->getQueryString();
      return ($value !== NULL) ? $value : '';
    }
    elseif ($this->requestStack->getCurrentRequest()->query->has($query_arg)) {
      $value = $this->requestStack->getCurrentRequest()->query->get($query_arg);
      if (is_array($value)) {
        return http_build_query($value);
      }
      elseif ($value !== '') {
        return $value;
      }
      return '?valueless?';
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($query_arg = NULL) {
    return new CacheableMetadata();
  }

}
