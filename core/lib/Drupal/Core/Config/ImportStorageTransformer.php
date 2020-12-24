<?php

namespace Drupal\Core\Config;

use Drupal\Core\Database\Connection;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The import storage transformer helps to use the configuration management api.
 *
 * This service does not implement an interface and is final because it is not
 * meant to be replaced, extended or used in a different context.
 * Its single purpose is to transform a storage for the import step of a
 * configuration synchronization by dispatching the import transformation event.
 */
final class ImportStorageTransformer {

  use StorageCopyTrait;

  /**
   * The name used to identify the lock.
   */
  const LOCK_NAME = 'config_import_transformer';

  /**
   * The event dispatcher to get changes to the configuration.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The drupal database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The normal lock for the duration of the request.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $requestLock;

  /**
   * The persistent lock which the config importer uses across requests.
   *
   * @see \Drupal\Core\Config\ConfigImporter::alreadyImporting()
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $persistentLock;

  /**
   * ImportStorageTransformer constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Lock\LockBackendInterface $requestLock
   *   The lock for the request.
   * @param \Drupal\Core\Lock\LockBackendInterface $persistentLock
   *   The persistent lock used by the config importer.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, Connection $connection, LockBackendInterface $requestLock, LockBackendInterface $persistentLock) {
    $this->eventDispatcher = $event_dispatcher;
    $this->connection = $connection;
    $this->requestLock = $requestLock;
    $this->persistentLock = $persistentLock;
  }

  /**
   * Transform the storage to be imported from.
   *
   * An import transformation is done before the config importer uses the
   * storage to synchronize the configuration. The transformation is also
   * done for displaying differences to review imports.
   * Importing in this context means the active drupal configuration is changed
   * with the ConfigImporter which may or may not be as part of the config
   * synchronization.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to transform for importing from it.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The transformed storage ready to be imported from.
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   *   Thrown when the lock could not be acquired.
   */
  public function transform(StorageInterface $storage) {
    // We use a database storage to reduce the memory requirement.
    $mutable = new DatabaseStorage($this->connection, 'config_import');

    if (!$this->persistentLock->lockMayBeAvailable(ConfigImporter::LOCK_NAME)) {
      // If the config importer is already importing, the transformation will
      // always be the one the config importer is already using. This makes sure
      // that even if the storage changes the importer continues importing the
      // same configuration.
      return $mutable;
    }

    // Acquire a lock to ensure that the storage is not changed when a
    // concurrent request tries to transform the storage. The lock will be
    // released at the end of the request.
    if (!$this->requestLock->acquire(self::LOCK_NAME)) {
      $this->requestLock->wait(self::LOCK_NAME);
      if (!$this->requestLock->acquire(self::LOCK_NAME)) {
        throw new StorageTransformerException("Cannot acquire config import transformer lock.");
      }
    }

    // Copy the sync configuration to the created mutable storage.
    self::replaceStorageContents($storage, $mutable);

    // Dispatch the event so that event listeners can alter the configuration.
    $this->eventDispatcher->dispatch(new StorageTransformEvent($mutable), ConfigEvents::STORAGE_TRANSFORM_IMPORT);

    // Return the storage with the altered configuration.
    return $mutable;
  }

}
