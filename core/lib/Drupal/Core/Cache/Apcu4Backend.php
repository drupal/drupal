<?php

namespace Drupal\Core\Cache;

@trigger_error(__NAMESPACE__ . '\Apcu4Backend is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Cache\ApcuBackend instead. See https://www.drupal.org/node/3063510.', E_USER_DEPRECATED);

/**
 * Stores cache items in the Alternative PHP Cache User Cache (APCu).
 *
 * This class is used with APCu versions >= 4.0.0 and < 5.0.0.
 *
 * @deprecated in drupal:8.8.0 and is removed from from drupal:9.0.0.
 *   Use \Drupal\Core\Cache\ApcuBackend instead.
 *
 * @see https://www.drupal.org/node/3063510
 */
class Apcu4Backend extends ApcuBackend {

  /**
   * {@inheritdoc}
   *
   * @return \APCIterator
   */
  protected function getIterator($search = NULL, $format = APC_ITER_ALL, $chunk_size = 100, $list = APC_LIST_ACTIVE) {
    return new \APCIterator('user', $search, $format, $chunk_size, $list);
  }

}
