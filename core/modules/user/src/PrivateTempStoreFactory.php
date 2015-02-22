<?php

/**
 * @file
 * Definition of Drupal\user\PrivateTempStoreFactory.
 */

namespace Drupal\user;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates a PrivateTempStore object for a given collecton.
 */
class PrivateTempStoreFactory {

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
   * @var int
   */
  protected $expire;

  /**
   * Constructs a Drupal\user\PrivateTempStoreFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection object used for this data.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock object used for this data.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  function __construct(KeyValueExpirableFactoryInterface $storage_factory, LockBackendInterface $lockBackend, AccountProxyInterface $current_user, RequestStack $request_stack, $expire = 604800) {
    $this->storageFactory = $storage_factory;
    $this->lockBackend = $lockBackend;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->expire = $expire;
  }

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
  function get($collection) {
    // Store the data for this collection in the database.
    $storage = $this->storageFactory->get("user.private_tempstore.$collection");
    return new PrivateTempStore($storage, $this->lockBackend, $this->currentUser, $this->requestStack, $this->expire);
  }

}
