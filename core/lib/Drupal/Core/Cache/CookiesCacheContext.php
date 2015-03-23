<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CookiesCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the CookiesCacheContext service, for "per cookie" caching.
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

}
