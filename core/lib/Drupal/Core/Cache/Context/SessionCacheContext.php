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
    $request = $this->requestStack->getCurrentRequest();
    if ($request->hasSession()) {
      return Crypt::hashBase64($request->getSession()->getId());
    }
    return 'none';
  }

}
