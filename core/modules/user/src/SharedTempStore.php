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
<<<<<<< HEAD
class SharedTempStore extends CoreSharedTempStore {
=======
class SharedTempStore {

  /**
   * The key/value storage object used for this data.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $storage;

  /**
   * The lock object used for this data.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   */
  protected $expire;

  /**
   * Constructs a new object for accessing data from a key/value store.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $storage
   *   The key/value storage object used for this data. Each storage object
   *   represents a particular collection of data and will contain any number
   *   of key/value pairs.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock_backend
   *   The lock object used for this data.
   * @param mixed $owner
   *   The owner key to store along with the data (e.g. a user or session ID).
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function __construct(KeyValueStoreExpirableInterface $storage, LockBackendInterface $lock_backend, $owner, RequestStack $request_stack, $expire = 604800) {
    $this->storage = $storage;
    $this->lockBackend = $lock_backend;
    $this->owner = $owner;
    $this->requestStack = $request_stack;
    $this->expire = $expire;
  }

  /**
   * Retrieves a value from this SharedTempStore for a given key.
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
   * Retrieves a value from this SharedTempStore for a given key.
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
    $value = (object) [
      'owner' => $this->owner,
      'data' => $value,
      'updated' => (int) $this->requestStack->getMasterRequest()->server->get('REQUEST_TIME'),
    ];
    return $this->storage->setWithExpireIfNotExists($key, $value, $this->expire);
  }

  /**
   * Stores a particular key/value pair in this SharedTempStore.
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
   *   by $this->user.
   *
   * @throws \Drupal\user\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
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
   * Stores a particular key/value pair in this SharedTempStore.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   *
   * @throws \Drupal\user\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
   */
  public function set($key, $value) {
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new TempStoreException("Couldn't acquire lock to update item '$key' in '{$this->storage->getCollectionName()}' temporary storage.");
      }
    }

    $value = (object) [
      'owner' => $this->owner,
      'data' => $value,
      'updated' => (int) $this->requestStack->getMasterRequest()->server->get('REQUEST_TIME'),
    ];
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
   *
   * @throws \Drupal\user\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
   */
  public function delete($key) {
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new TempStoreException("Couldn't acquire lock to delete item '$key' from {$this->storage->getCollectionName()} temporary storage.");
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
   *
   * @throws \Drupal\user\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
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

>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
}
