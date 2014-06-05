<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\TimeZoneCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the TimeZoneCacheContext service, for "per time zone" caching.
 *
 * @see \Drupal\Core\Session\SessionManager::initialize()
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
    // date_default_timezone_set() is called in SessionManager::initialize(), so
    // we can safely retrieve the timezone.
    return date_default_timezone_get();
  }

}
