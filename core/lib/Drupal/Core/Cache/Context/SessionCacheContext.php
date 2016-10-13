<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Component\Utility\Crypt;

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
    $sid = $this->requestStack->getCurrentRequest()->getSession()->getId();
    return Crypt::hashBase64($sid);
  }

}
