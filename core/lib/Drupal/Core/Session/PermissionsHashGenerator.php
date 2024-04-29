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
   * Constructs a PermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $privateKey
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\Core\Session\AccessPolicyProcessorInterface $processor
   *   The access policy processor.
   */
  public function __construct(
    protected PrivateKey $privateKey,
    protected CacheBackendInterface $static,
    protected AccessPolicyProcessorInterface $processor,
  ) {
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
