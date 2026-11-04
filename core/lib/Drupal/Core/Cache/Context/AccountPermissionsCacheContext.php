<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\PermissionsHashGeneratorInterface;

/**
 * The account permission cache context for "per permission" caching.
 *
 * Cache context ID: 'user.permissions'.
 */
class AccountPermissionsCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * Constructs a new UserCacheContext service.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Session\PermissionsHashGeneratorInterface $permissionsHashGenerator
   *   The permissions hash generator.
   */
  public function __construct(
    AccountInterface $user,
    protected PermissionsHashGeneratorInterface $permissionsHashGenerator,
  ) {
    $this->user = $user;
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
    return $this->permissionsHashGenerator->generate($this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return $this->permissionsHashGenerator->getCacheableMetadata($this->user);
  }

}
