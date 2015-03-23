<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\UrlCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the UrlCacheContext service, for "per page" caching.
 */
class UrlCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('URL');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

}
