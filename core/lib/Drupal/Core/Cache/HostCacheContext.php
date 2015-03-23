<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\HostCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the HostCacheContext service, for "per host" caching.
 *
 * A "host" is defined as the combination of URI scheme, domain name and port.
 *
 * @see Symfony\Component\HttpFoundation::getSchemeAndHttpHost()
 */
class HostCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Host');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
  }

}
