<?php

namespace Drupal\user;

use Drupal\Core\TempStore\SharedTempStore as CoreSharedTempStore;

@trigger_error('\Drupal\user\SharedTempStore is scheduled for removal in Drupal 9.0.0. Use \Drupal\Core\TempStore\SharedTempStore instead. See https://www.drupal.org/node/2935639.', E_USER_DEPRECATED);

/**
 * In order to preserve BC alias the core exception.
 */
if (!class_exists('\Drupal\user\TempStoreException')) {
  class_alias('\Drupal\Core\TempStore\TempStoreException', '\Drupal\user\TempStoreException');
}

/**
 * Stores and retrieves temporary data for a given owner.
 *
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\TempStore\SharedTempStore instead.
 *
 * @see \Drupal\Core\TempStore\SharedTempStore
 * @see https://www.drupal.org/node/2935639
 */
class SharedTempStore extends CoreSharedTempStore {
}
