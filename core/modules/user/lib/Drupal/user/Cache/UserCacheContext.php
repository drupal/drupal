<?php

/**
 * @file
 * Contains \Drupal\user\Cache\UserCacheContext.
 */

namespace Drupal\user\Cache;

use Drupal\Core\Cache\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the UserCacheContext service, for "per user" caching.
 */
class UserCacheContext implements CacheContextInterface {

  /**
   * Constructs a new UserCacheContext service.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(AccountInterface $user) {
    $this->user = $user;
  }

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
    return "u." . $this->user->id();
  }

}
