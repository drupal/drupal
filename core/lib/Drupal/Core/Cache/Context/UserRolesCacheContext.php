<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\UserRolesCacheContext.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Defines the UserRolesCacheContext service, for "per role" caching.
 */
class UserRolesCacheContext extends UserCacheContext implements CalculatedCacheContextInterface{

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("User's roles");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($role = NULL) {
    if ($role === NULL) {
      return 'r.' . implode(',', $this->user->getRoles());
    }
    else {
      return 'r.' . $role . '.' . (in_array($role, $this->user->getRoles()) ? '0' : '1');
    }
  }

}
