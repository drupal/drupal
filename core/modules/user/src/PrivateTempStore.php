<?php

namespace Drupal\user;

use Drupal\Core\TempStore\PrivateTempStore as CorePrivateTempStore;

@trigger_error('\Drupal\user\PrivateTempStore is scheduled for removal in Drupal 9.0.0. Use \Drupal\Core\TempStore\PrivateTempStore instead. See https://www.drupal.org/node/2935639.', E_USER_DEPRECATED);

/**
 * In order to preserve BC alias the core exception.
 */
if (!class_exists('\Drupal\user\TempStoreException')) {
  class_alias('\Drupal\Core\TempStore\TempStoreException', '\Drupal\user\TempStoreException');
}

/**
 * Stores and retrieves temporary data for a given owner.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\TempStore\PrivateTempStore instead.
 *
 * @see \Drupal\Core\TempStore\PrivateTempStore
 * @see https://www.drupal.org/node/2935639
 */
class PrivateTempStore extends CorePrivateTempStore {
}
