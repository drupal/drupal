<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\TimeZoneCacheContext.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Defines the TimeZoneCacheContext service, for "per time zone" caching.
 *
 * Cache context ID: 'timezone'.
 *
 * @see \Drupal\Core\Session\AccountProxy::setAccount()
 */
class TimeZoneCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Time zone");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // date_default_timezone_set() is called in AccountProxy::setAccount(), so
    // we can safely retrieve the timezone.
    return date_default_timezone_get();
  }

}
