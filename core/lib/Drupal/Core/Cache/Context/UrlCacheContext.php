<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\UrlCacheContext.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Defines the UrlCacheContext service, for "per page" caching.
 *
 * Cache context ID: 'url'.
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
