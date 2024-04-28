<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\CacheableMetadata;
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
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * The access policy processor.
   *
   * @var \Drupal\Core\Session\AccessPolicyProcessorInterface
   */
  protected $processor;

  /**
   * Constructs a PermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\Core\Session\AccessPolicyProcessorInterface|\Drupal\Core\Cache\CacheBackendInterface $processor
   *   The access policy processor.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $static, AccessPolicyProcessorInterface|CacheBackendInterface $processor) {
    $this->privateKey = $private_key;
    if ($processor instanceof CacheBackendInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $processor argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3402110', E_USER_DEPRECATED);
      $this->static = $processor;
      $this->processor = \Drupal::service('access_policy_processor');
      return;
    }
    $this->static = $static;
    $this->processor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(AccountInterface $account) {
    // We can use a simple per-user static cache here because we already cache
    // the permissions more efficiently in the access policy processor. On top
    // of that, there is only a tiny chance of a hash being generated for more
    // than one account during a single request.
    $cid = 'permissions_hash_' . $account->id();

    // Retrieve the hash from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }

    // Otherwise hash the permissions and store them in the static cache.
    $calculated_permissions = $this->processor->processAccessPolicies($account);
    $item = $calculated_permissions->getItem();

    // This should never happen, but in case nothing defined permissions for the
    // current user, even if empty, we need to have _some_ hash too.
    if ($item === FALSE) {
      $hash = 'no-access-policies';
    }
    // If the calculated permissions item grants admin rights, we can simplify
    // the entry by setting it to 'is-admin' rather than calculating an actual
    // hash. This is because admin flagged calculated permissions
    // automatically empty out the permissions array.
    elseif ($item->isAdmin()) {
      $hash = 'is-admin';
    }
    // Sort the permissions by name to ensure we don't get mismatching hashes
    // for people with the same permissions, just because the order of the
    // permissions happened to differ.
    else {
      $permissions = $item->getPermissions();
      sort($permissions);
      $hash = $this->hash(serialize($permissions));
    }

    $this->static->set($cid, $hash, Cache::PERMANENT, $calculated_permissions->getCacheTags());
    return $hash;
  }

  /**
   * Generates a hash that uniquely identifies the user's permissions.
   *
   * @param string[] $roles
   *   The user's roles.
   *
   * @return string
   *   The permissions hash.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3435842
   */
  protected function doGenerate(array $roles) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3435842', E_USER_DEPRECATED);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(AccountInterface $account): CacheableMetadata {
    return CacheableMetadata::createFromObject($this->processor->processAccessPolicies($account));
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
