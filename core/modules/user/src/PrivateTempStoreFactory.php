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
<<<<<<< HEAD
class PrivateTempStoreFactory extends CorePrivateTempStoreFactory {
=======
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
   * @var int
   */
  protected $expire;

  /**
   * Constructs a Drupal\user\PrivateTempStoreFactory object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $storage_factory
   *   The key/value store factory.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock_backend
   *   The lock object used for this data.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current account.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function __construct(KeyValueExpirableFactoryInterface $storage_factory, LockBackendInterface $lock_backend, AccountProxyInterface $current_user, RequestStack $request_stack, $expire = 604800) {
    $this->storageFactory = $storage_factory;
    $this->lockBackend = $lock_backend;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->expire = $expire;
  }
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd

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
