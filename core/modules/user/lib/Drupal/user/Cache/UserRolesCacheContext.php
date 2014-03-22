<?php

/**
 * @file
 * Contains \Drupal\user\Cache\UserRolesCacheContext.
 */

namespace Drupal\user\Cache;

use Drupal\Core\Cache\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the UserRolesCacheContext service, for "per role" caching.
 */
class UserRolesCacheContext implements CacheContextInterface {

  /**
   * Constructs a new UserRolesCacheContext service.
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
    return t("User's roles");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return 'r.' . implode(',', $this->user->getRoles());
  }

}
