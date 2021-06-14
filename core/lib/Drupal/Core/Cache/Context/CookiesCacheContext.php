<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the CookiesCacheContext service, for "per cookie" caching.
 *
 * Cache context ID: 'cookies' (to vary by all cookies).
 * Calculated cache context ID: 'cookies:%name', e.g. 'cookies:device_type' (to
 * vary by the 'device_type' cookie).
 */
class CookiesCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('HTTP cookies');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($cookie = NULL) {
    if ($cookie === NULL) {
      return $this->requestStack->getCurrentRequest()->cookies->all();
    }
    else {
      return $this->requestStack->getCurrentRequest()->cookies->get($cookie);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($cookie = NULL) {
    return new CacheableMetadata();
  }

}
