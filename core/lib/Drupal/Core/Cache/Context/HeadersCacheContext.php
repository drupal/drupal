<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the HeadersCacheContext service, for "per header" caching.
 *
 * Cache context ID: 'headers' (to vary by all headers).
 * Calculated cache context ID: 'headers:%name', e.g. 'headers:X-Something' (to
 * vary by the 'X-Something' header).
 */
class HeadersCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('HTTP headers');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($header = NULL) {
    if ($header === NULL) {
      return $this->requestStack->getCurrentRequest()->headers->all();
    }
    else {
      return $this->requestStack->getCurrentRequest()->headers->get($header);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($header = NULL) {
    return new CacheableMetadata();
  }

}
