<?php

/**
 * @file
 * Contains \Drupal\user\PermissionsHash.
 */

namespace Drupal\user;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Generates and caches the permissions hash for a user.
 */
class PermissionsHash implements PermissionsHashInterface {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The cache backend interface to use for the permission hash cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a PermissionsHash object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the permission hash cache.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $cache) {
    $this->privateKey = $private_key;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   *
   * Cached by role, invalidated whenever permissions change.
   */
  public function generate(AccountInterface $account) {
    $sorted_roles = $account->getRoles();
    sort($sorted_roles);
    $role_list = implode(',', $sorted_roles);
    if ($cache = $this->cache->get("user_permissions_hash:$role_list")) {
      $permissions_hash = $cache->data;
    }
    else {
      $permissions_hash = $this->doGenerate($sorted_roles);
      $this->cache->set("user_permissions_hash:$role_list", $permissions_hash, Cache::PERMANENT, array('user_role' => $sorted_roles));
    }

    return $permissions_hash;
  }

  /**
   * Generates a hash that uniquely identifies the user's permissions.
   *
   * @param \Drupal\user\Entity\Role[] $roles
   *   The user's roles.
   *
   * @return string
   *   The permissions hash.
   */
  protected function doGenerate(array $roles) {
    // @todo Once Drupal gets rid of user_role_permissions(), we should be able
    // to inject the user role controller and call a method on that instead.
    $permissions_by_role = user_role_permissions($roles);
    foreach ($permissions_by_role as $role => $permissions) {
      sort($permissions);
      $permissions_by_role[$role] = $permissions;
    }
    return hash('sha256', $this->privateKey->get() . drupal_get_hash_salt() . serialize($permissions_by_role));
  }

}
