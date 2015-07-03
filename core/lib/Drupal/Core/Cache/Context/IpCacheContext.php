<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\IpCacheContext.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Defines the IpCacheContext service, for "per IP address" caching.
 *
 * Cache context ID: 'ip'.
 */
class IpCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('IP address');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getClientIp();
  }

}
