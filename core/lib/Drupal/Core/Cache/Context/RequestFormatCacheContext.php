<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\RequestFormatCacheContext.
 */

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the RequestFormatCacheContext service, for "per format" caching.
 *
 * Cache context ID: 'request_format'.
 */
class RequestFormatCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Request format');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getRequestFormat();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
