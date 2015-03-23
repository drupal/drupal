<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\IpCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the IpCacheContext service, for "per IP address" caching.
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
