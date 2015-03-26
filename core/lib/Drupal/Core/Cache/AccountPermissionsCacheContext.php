<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\UserRolesCacheContext.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\PermissionsHashGeneratorInterface;

/**
 * Defines the AccountPermissionsCacheContext service, for "per permission" caching.
 */
class AccountPermissionsCacheContext extends UserCacheContext implements CacheContextInterface{

  /**
   * The permissions hash generator.
   *
   * @var \Drupal\user\PermissionsHashInterface
   */
  protected $permissionsHashGenerator;

  /**
   * Constructs a new UserCacheContext service.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\user\PermissionsHashInterface $permissions_hash_generator
   *   The permissions hash generator.
   */
  public function __construct(AccountInterface $user, PermissionsHashGeneratorInterface $permissions_hash_generator) {
    $this->user = $user;
    $this->permissionsHashGenerator = $permissions_hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Account's permissions");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return 'ph.' . $this->permissionsHashGenerator->generate($this->user);
  }

}
