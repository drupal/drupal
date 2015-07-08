<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\AccountPermissionsCacheContext.
 */

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\PermissionsHashGeneratorInterface;

/**
 * Defines the AccountPermissionsCacheContext service, for "per permission" caching.
 *
 * Cache context ID: 'user.permissions'.
 */
class AccountPermissionsCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * The permissions hash generator.
   *
   * @var \Drupal\Core\Session\PermissionsHashGeneratorInterface
   */
  protected $permissionsHashGenerator;

  /**
   * Constructs a new UserCacheContext service.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Session\PermissionsHashGeneratorInterface $permissions_hash_generator
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

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();
    $tags = [];
    foreach ($this->user->getRoles() as $rid) {
      $tags[] = "config:user.role.$rid";
    }

    return $cacheable_metadata->setCacheTags($tags);
  }

}
