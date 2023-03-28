<?php

namespace Drupal\Core\Session;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Site\Settings;

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
   * The cache backend interface to use for the persistent cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * Constructs a PermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the persistent cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|null $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $cache, CacheBackendInterface $static, protected ?EntityTypeManagerInterface $entityTypeManager = NULL) {
    $this->privateKey = $private_key;
    $this->cache = $cache;
    $this->static = $static;
    if ($this->entityTypeManager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entityTypeManager argument is deprecated in drupal:10.1.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3348138', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
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
    $cid = "user_permissions_hash:$role_list";
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    else {
      $tags = Cache::buildTags('config:user.role', $sorted_roles, '.');
      if ($cache = $this->cache->get($cid)) {
        $permissions_hash = $cache->data;
      }
      else {
        $permissions_hash = $this->doGenerate($sorted_roles);
        $this->cache->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
      }
      $this->static->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
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
    $permissions_by_role = [];
    /** @var \Drupal\user\RoleInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage('user_role')->loadMultiple($roles);
    foreach ($roles as $role) {
      // Note that for admin roles (\Drupal\user\RoleInterface::isAdmin()), the
      // permissions returned will be empty ($permissions = []). Therefore the
      // presence of the role ID as a key in $permissions_by_role is essential
      // to ensure that the hash correctly recognizes admin roles. (If the hash
      // was based solely on the union of $permissions, the admin roles would
      // effectively be no-ops, allowing for hash collisions.)
      $permissions_by_role[$role] = isset($entities[$role]) ? $entities[$role]->getPermissions() : [];
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
