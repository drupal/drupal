<?php

/**
 * @file
 * Contains \Drupal\Core\Session\PermissionsHashGenerator.
 */

namespace Drupal\Core\Session;

use Drupal\Core\PrivateKey;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\Entity\Role;

/**
 * Generates and caches the permissions hash for a user.
 */
class PermissionsHashGenerator implements PermissionsHashGeneratorInterface {

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
   * Constructs a PermissionsHashGenerator object.
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
    // User 1 is the super user, and can always access all permissions. Use a
    // different, unique identifier for the hash.
    if ($account->id() == 1) {
      return $this->hash('is-super-user');
    }

    $sorted_roles = $account->getRoles();
    sort($sorted_roles);
    $role_list = implode(',', $sorted_roles);
    if ($cache = $this->cache->get("user_permissions_hash:$role_list")) {
      $permissions_hash = $cache->data;
    }
    else {
      $permissions_hash = $this->doGenerate($sorted_roles);
      $tags = Cache::buildTags('config:user.role', $sorted_roles, '.');
      $this->cache->set("user_permissions_hash:$role_list", $permissions_hash, Cache::PERMANENT, $tags);
    }

    return $permissions_hash;
  }

  /**
   * Generates a hash that uniquely identifies the user's permissions.
   *
   * @param string[] $roles
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
      // Note that for admin roles (\Drupal\user\RoleInterface::isAdmin()), the
      // permissions returned will be empty ($permissions = []). Therefore the
      // presence of the role ID as a key in $permissions_by_role is essential
      // to ensure that the hash correctly recognizes admin roles. (If the hash
      // was based solely on the union of $permissions, the admin roles would
      // effectively be no-ops, allowing for hash collisions.)
      $permissions_by_role[$role] = $permissions;
    }
    return $this->hash(serialize($permissions_by_role));
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

}
