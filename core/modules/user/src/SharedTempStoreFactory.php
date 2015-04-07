<?php

/**
 * @file
 * Definition of Drupal\user\SharedTempStoreFactory.
 */

namespace Drupal\user;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates a shared temporary storage for a collection.
 */
class SharedTempStoreFactory {

  /**
   * The storage factory creating the backend to store the data.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $storageFactory;

  /**
   * The lock object used for this data.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface $lockBackend
   */
  protected $lockBackend;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The time to live for items in seconds.
   *
   * @var int
   */
  protected $expire;

  /**
   * Constructs a Drupal\user\SharedTempStoreFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection object used for this data.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock object used for this data.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  function __construct(KeyValueExpirableFactoryInterface $storage_factory, LockBackendInterface $lockBackend, RequestStack $request_stack, $expire = 604800) {
    $this->storageFactory = $storage_factory;
    $this->lockBackend = $lockBackend;
    $this->requestStack = $request_stack;
    $this->expire = $expire;
  }

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
  function get($collection, $owner = NULL) {
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
