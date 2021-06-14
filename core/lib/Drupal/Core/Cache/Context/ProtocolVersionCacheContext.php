<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the ProtocolVersionCacheContext service, for "per protocol" caching.
 *
 * Useful to differentiate between HTTP/1.1 and HTTP/2.0 responses for example,
 * to allow responses to be optimized for protocol-specific characteristics.
 *
 * Cache context ID: 'protocol_version'.
 */
class ProtocolVersionCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Protocol version');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getProtocolVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
