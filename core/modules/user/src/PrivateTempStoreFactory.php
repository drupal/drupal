<?php

namespace Drupal\user;

use Drupal\Core\TempStore\PrivateTempStoreFactory as CorePrivateTempStoreFactory;

@trigger_error('\Drupal\user\PrivateTempStoreFactory is scheduled for removal in Drupal 9.0.0. Use \Drupal\Core\TempStore\PrivateTempStoreFactory instead. See https://www.drupal.org/node/2935639.', E_USER_DEPRECATED);

/**
 * Creates a PrivateTempStore object for a given collection.
 *
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\TempStore\PrivateTempStoreFactory instead.
 *
 * @see \Drupal\Core\TempStore\PrivateTempStoreFactory
 * @see https://www.drupal.org/node/2935639
 */
class PrivateTempStoreFactory extends CorePrivateTempStoreFactory {

  /**
   * Creates a PrivateTempStore.
   *
   * @param string $collection
   *   The collection name to use for this key/value store. This is typically
   *   a shared namespace or module name, e.g. 'views', 'entity', etc.
   *
   * @return \Drupal\user\PrivateTempStore
   *   An instance of the key/value store.
   */
  public function get($collection) {
    // Store the data for this collection in the database.
    $storage = $this->storageFactory->get("user.private_tempstore.$collection");
    return new PrivateTempStore($storage, $this->lockBackend, $this->currentUser, $this->requestStack, $this->expire);
  }

}
