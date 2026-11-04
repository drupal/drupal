<?php

namespace Drupal\Core\TempStore;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates a shared temporary storage for a collection.
 */
class SharedTempStoreFactory {

  /**
   * Constructs a Drupal\Core\TempStore\SharedTempStoreFactory object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $storageFactory
   *   The key/value store factory.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock object used for this data.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function __construct(
    protected KeyValueExpirableFactoryInterface $storageFactory,
    protected LockBackendInterface $lockBackend,
    protected RequestStack $requestStack,
    protected AccountProxyInterface $currentUser,
    protected $expire = 604800,
  ) {
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
   * @return \Drupal\Core\TempStore\SharedTempStore
   *   An instance of the key/value store.
   */
  public function get($collection, $owner = NULL) {
    // Use the currently authenticated user ID or the active user ID unless
    // the owner is overridden.
    if (!isset($owner)) {
      $owner = $this->currentUser->id();
      if ($this->currentUser->isAnonymous()) {
        $owner = $this->requestStack->getSession()->get('core.tempstore.shared.owner', Crypt::randomBytesBase64());
      }
    }

    // Store the data for this collection in the database.
    $storage = $this->storageFactory->get("tempstore.shared.$collection");
    return new SharedTempStore($storage, $this->lockBackend, $owner, $this->requestStack, $this->currentUser, $this->expire);
  }

}
