<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the UrlCacheContext service, for "per page" caching.
 *
 * Cache context ID: 'url'.
 */
class UrlCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

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

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
