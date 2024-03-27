<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the user permissions hash generator interface.
 */
interface PermissionsHashGeneratorInterface {

  /**
   * Generates a hash that uniquely identifies a user's permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to get the permissions hash.
   *
   * @return string
   *   A permissions hash.
   */
  public function generate(AccountInterface $account);

  /**
   * Gets the cacheability metadata for the generated hash.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to get the permissions hash.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   A cacheable metadata object.
   */
  public function getCacheableMetadata(AccountInterface $account): CacheableMetadata;

}
