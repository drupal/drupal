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
 * @deprecated in Drupal 8.5.x, to be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\TempStore\PrivateTempStore instead.
 *
 * @see \Drupal\Core\TempStore\PrivateTempStore
 * @see https://www.drupal.org/node/2935639
 */
<<<<<<< HEAD
class PrivateTempStore extends CorePrivateTempStore {
=======
class PrivateTempStore {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function __construct(KeyValueStoreExpirableInterface $storage, LockBackendInterface $lock_backend, AccountProxyInterface $current_user, RequestStack $request_stack, $expire = 604800) {
    $this->storage = $storage;
    $this->lockBackend = $lock_backend;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->expire = $expire;
  }

  /**
   * Retrieves a value from this PrivateTempStore for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return mixed
   *   The data associated with the key, or NULL if the key does not exist.
   */
  public function get($key) {
    $key = $this->createkey($key);
    if (($object = $this->storage->get($key)) && ($object->owner == $this->getOwner())) {
      return $object->data;
    }
  }

  /**
   * Stores a particular key/value pair in this PrivateTempStore.
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
    $key = $this->createkey($key);
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new TempStoreException("Couldn't acquire lock to update item '$key' in '{$this->storage->getCollectionName()}' temporary storage.");
      }
    }

    $value = (object) [
      'owner' => $this->getOwner(),
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
    $key = $this->createkey($key);
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
   * @return bool
   *   TRUE if the object was deleted or does not exist, FALSE if it exists but
   *   is not owned by $this->owner.
   *
   * @throws \Drupal\user\TempStoreException
   *   Thrown when a lock for the backend storage could not be acquired.
   */
  public function delete($key) {
    $key = $this->createkey($key);
    if (!$object = $this->storage->get($key)) {
      return TRUE;
    }
    elseif ($object->owner != $this->getOwner()) {
      return FALSE;
    }
    if (!$this->lockBackend->acquire($key)) {
      $this->lockBackend->wait($key);
      if (!$this->lockBackend->acquire($key)) {
        throw new TempStoreException("Couldn't acquire lock to delete item '$key' from '{$this->storage->getCollectionName()}' temporary storage.");
      }
    }
    $this->storage->delete($key);
    $this->lockBackend->release($key);
    return TRUE;
  }

  /**
   * Ensures that the key is unique for a user.
   *
   * @param string $key
   *   The key.
   *
   * @return string
   *   The unique key for the user.
   */
  protected function createkey($key) {
    return $this->getOwner() . ':' . $key;
  }

  /**
   * Gets the current owner based on the current user or the session ID.
   *
   * @return string
   *   The owner.
   */
  protected function getOwner() {
    return $this->currentUser->id() ?: $this->requestStack->getCurrentRequest()->getSession()->getId();
  }

>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
}
