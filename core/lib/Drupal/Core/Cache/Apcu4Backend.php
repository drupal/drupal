<?php

namespace Drupal\Core\Cache;

/**
 * Stores cache items in the Alternative PHP Cache User Cache (APCu).
 *
 * This class is used with APCu versions >= 4.0.0 and < 5.0.0.
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
