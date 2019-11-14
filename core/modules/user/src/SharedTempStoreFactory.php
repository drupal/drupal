<?php

namespace Drupal\user;

use Drupal\Core\TempStore\SharedTempStoreFactory as CoreSharedTempStoreFactory;

@trigger_error('\Drupal\user\SharedTempStoreFactory is scheduled for removal in Drupal 9.0.0. Use \Drupal\Core\TempStore\SharedTempStoreFactory instead. See https://www.drupal.org/node/2935639.', E_USER_DEPRECATED);

/**
 * Creates a shared temporary storage for a collection.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\TempStore\SharedTempStoreFactory instead.
 *
 * @see \Drupal\Core\TempStore\SharedTempStoreFactory
 * @see https://www.drupal.org/node/2935639
 */
class SharedTempStoreFactory extends CoreSharedTempStoreFactory {

  /**
   * Creates a SharedTempStore for the current user or anonymous session.
   *
   * @param string $collection
   *   The collection name to use for this key/value store. This is typically
   *   a shared namespace or module name, e.g. 'views', 'entity', etc.
   * @param mixed $owner
   *   (optional) The owner of this SharedTempStore. By default, the
   *   SharedTempStore is owned by the currently authenticated user, or by the
   *   active anonymous session if no user is logged in.
   *
   * @return \Drupal\user\SharedTempStore
   *   An instance of the key/value store.
   */
  public function get($collection, $owner = NULL) {
    // Use the currently authenticated user ID or the active user ID unless
    // the owner is overridden.
    if (!isset($owner)) {
      $owner = \Drupal::currentUser()->id() ?: session_id();
    }

    // Store the data for this collection in the database.
    $storage = $this->storageFactory->get("user.shared_tempstore.$collection");
    return new SharedTempStore($storage, $this->lockBackend, $owner, $this->requestStack, $this->expire);
  }

}
