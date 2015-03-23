<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\IsSuperUserCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the IsSuperUserCacheContext service, for "super user or not" caching.
 */
class IsSuperUserCacheContext extends UserCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is super user');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return ((int) $this->user->id()) === 1 ? '1' : '0';
  }

}
