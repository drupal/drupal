<?php

/**
 * @file
 * Contains Drupal\user\PrivateTempStore.
 */

namespace Drupal\user;

use Drupal\Component\Utility\String;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stores and retrieves temporary data for a given owner.
 *
 * A PrivateTempStore can be used to make temporary, non-cache data available
 * across requests. The data for the PrivateTempStore is stored in one
 * key/value collection. PrivateTempStore data expires automatically after a
 * given timeframe.
 *
 * The PrivateTempStore is different from a cache, because the data in it is not
 * yet saved permanently and so it cannot be rebuilt. Typically, the
 * PrivateTempStore might be used to store work in progress that is later saved
 * permanently elsewhere, e.g. autosave data, multistep forms, or in-progress
 * changes to complex configuration that are not ready to be saved.
 *
 * The PrivateTempStore differs from the SharedTempStore in that all keys are
 * ensured to be unique for a particular user and users can never share data. If
 * you want to be able to share data between users or use it for locking, use
 * \Drupal\user\SharedTempStore.
 */
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
   * @param KeyValueStoreExpirableInterface $storage
   *   The key/value storage object used for this data. Each storage object
   *   represents a particular collection of data and will contain any number
   *   of key/value pairs.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock object used for this data.
   * @param mixed $owner
   *   The owner key to store along with the data (e.g. a user or session ID).
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function __construct(KeyValueStoreExpirableInterface $storage, LockBackendInterface $lockBackend, AccountProxyInterface $current_user, RequestStack $request_stack, $expire = 604800) {
    $this->storage = $storage;
    $this->lockBackend = $lockBackend;
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
   */
  public function set($key, $value) {
    $key = $this->createkey($key);
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
      'owner' => $this->getOwner(),
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
        throw new TempStoreException(String::format("Couldn't acquire lock to delete item %key from %collection temporary storage.", array(
          '%key' => $key,
          '%collection' => $this->storage->getCollectionName(),
        )));
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
}
