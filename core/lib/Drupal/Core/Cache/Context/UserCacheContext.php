<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the UserCacheContext service, for "per user" caching.
 *
 * Cache context ID: 'user'.
 */
class UserCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->user->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
