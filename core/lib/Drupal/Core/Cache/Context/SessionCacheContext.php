<?php

namespace Drupal\Core\Cache\Context;

/**
 * Defines the SessionCacheContext service, for "per session" caching.
 *
 * Cache context ID: 'session'.
 */
class SessionCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Session');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getSession()->getId();
  }

}
