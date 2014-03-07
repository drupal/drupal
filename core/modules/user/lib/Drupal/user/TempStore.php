<?php

/**
 * @file
 * Contains Drupal\user\TempStore.
 */

namespace Drupal\user;

use Drupal\Component\Utility\String;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Stores and retrieves temporary data for a given owner.
 *
 * A TempStore can be used to make temporary, non-cache data available across
 * requests. The data for the TempStore is stored in one key/value collection.
 * TempStore data expires automatically after a given timeframe.
 *
 * The TempStore is different from a cache, because the data in it is not yet
 * saved permanently and so it cannot be rebuilt. Typically, the TempStore
 * might be used to store work in progress that is later saved permanently
 * elsewhere, e.g. autosave data, multistep forms, or in-progress changes
 * to complex configuration that are not ready to be saved.
 *
 * Each TempStore belongs to a particular owner (e.g. a user, session, or
 * process). Multiple owners may use the same key/value collection, and the
 * owner is stored along with the key/value pair.
 *
 * Every key is unique within the collection, so the TempStore can check
 * whether a particular key is already set by a different owner. This is
 * useful for informing one owner that the data is already in use by another;
 * for example, to let one user know that another user is in the process of
 * editing certain data, or even to restrict other users from editing it at
 * the same time. It is the responsibility of the implementation to decide
 * when and whether one owner can use or update another owner's data.
 */
class TempStore {

  /**
   * The key/value storage object used for this data.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpireInterface;
   */
  protected $storage;

  /**
   * The lock object used for this data.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * The owner key to store along with the data (e.g. a user or session ID).
   *
   * @var mixed
   */
  protected $owner;

  /**
   * The time to live for items in seconds.
   *
   * By default, data is stored for one week (604800 seconds) before expiring.
   *
   * @var int
   *
   * @todo Currently, this property is not exposed anywhere, and so the only
   *   way to override it is by extending the class.
   */
  protected $expire = 604800;

  /**
   * Constructs a new object for accessing data from a key/value store.
   *
   * @param KeyValueStoreExpireInterface $storage
   *   The key/value storage object used for this data. Each storage object
   *   represents a particular collection of data and will contain any number
   *   of key/value pairs.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock object used for this data.
   * @param mixed $owner
   *   The owner key to store along with the data (e.g. a user or session ID).
   */
  public function __construct(KeyValueStoreExpirableInterface $storage, LockBackendInterface $lockBackend, $owner) {
    $this->storage = $storage;
    $this->lockBackend = $lockBackend;
    $this->owner = $owner;
  }

  /**
   * Retrieves a value from this TempStore for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return mixed
   *   The data associated with the key, or NULL if the key does not exist.
   */
  public function get($key) {
    if ($object = $this->storage->get($key)) {
      return $object->data;
    }
  }

  /**
   * Retrieves a value from this TempStore for a given key.
   *
   * Only returns the value if the value is owned by $this->owner.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return mixed
   *   The data associated with the key, or NULL if the key does not exist.
   */
  public function getIfOwner($key) {
    if (($object = $this->storage->get($key)) && ($object->owner == $this->owner)) {
      return $object->data;
    }
  }

  /**
   * Stores a particular key/value pair only if the key doesn't already exist.
   *
   * @param string $key
   *   The key of the data to check and store.
   * @param mixed $value
   *   The data to store.
   *
   * @return bool
   *   TRUE if the data was set, or FALSE if it already existed.
   */
  public function setIfNotExists($key, $value) {
    $value = (object) array(
      'owner' => $this->owner,
      'data' => $value,
      'updated' => REQUEST_TIME,
    );
    return $this->storage->setWithExpireIfNotExists($key, $value, $this->expire);
  }

  /**
   * Stores a particular key/value pair in this TempStore.
   *
   * Only stores the given key/value pair if it does not exist yet or is owned
   * by $this->owner.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   *
   * @return bool
   *   TRUE if the data was set, or FALSE if it already exists and is not owned
   * by $this->user.
   */
  public function setIfOwner($key, $value) {
    if ($this->setIfNotExists($key, $value)) {
      return TRUE;
    }

    if (($object = $this->storage->get($key)) && ($object->owner == $this->owner)) {
      $this->set($key, $value);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Stores a particular key/value pair in this TempStore.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function set($key, $value) {
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new TempStoreException(String::format("Couldn't acquire lock to update item %key in %collection temporary storage.", array(
          '%key' => $key,
          '%collection' => $this->storage->getCollectionName(),
        )));
      }
    }

    $value = (object) array(
      'owner' => $this->owner,
      'data' => $value,
      'updated' => REQUEST_TIME,
    );
    $this->storage->setWithExpire($key, $value, $this->expire);
    $this->lockBackend->release($key);
  }

  /**
   * Returns the metadata associated with a particular key/value pair.
   *
   * @param string $key
   *   The key of the data to store.
   *
   * @return mixed
   *   An object with the owner and updated time if the key has a value, or
   *   NULL otherwise.
   */
  public function getMetadata($key) {
    // Fetch the key/value pair and its metadata.
    $object = $this->storage->get($key);
    if ($object) {
      // Don't keep the data itself in memory.
      unset($object->data);
      return $object;
    }
  }

  /**
   * Deletes data from the store for a given key and releases the lock on it.
   *
   * @param string $key
   *   The key of the data to delete.
   */
  public function delete($key) {
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new TempStoreException(String::format("Couldn't acquire lock to delete item %key from %collection temporary storage.", array(
          '%key' => $key,
          '%collection' => $this->storage->getCollectionName(),
        )));
      }
    }
    $this->storage->delete($key);
    $this->lockBackend->release($key);
  }

  /**
   * Deletes data from the store for a given key and releases the lock on it.
   *
   * Only delete the given key if it is owned by $this->owner.
   *
   * @param string $key
   *   The key of the data to delete.
   *
   * @return bool
   *   TRUE if the object was deleted or does not exist, FALSE if it exists but
   *   is not owned by $this->owner.
   */
  public function deleteIfOwner($key) {
    if (!$object = $this->storage->get($key)) {
      return TRUE;
    }
    elseif ($object->owner == $this->owner) {
      $this->delete($key);
      return TRUE;
    }

    return FALSE;
  }

}
