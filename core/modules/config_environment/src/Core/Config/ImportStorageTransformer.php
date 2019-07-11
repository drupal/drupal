<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\Core\Config in #2991683.
// Use this class with its class alias Drupal\Core\Config\ImportStorageTransformer
// @codingStandardsIgnoreEnd
namespace Drupal\config_environment\Core\Config;

use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;

/**
 * Class ImportStorageTransformer.
 *
 * @internal
 */
class ImportStorageTransformer {

  use StorageCopyTrait;

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
   * ImportStorageTransformer constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, Connection $connection) {
    $this->eventDispatcher = $event_dispatcher;
    $this->connection = $connection;
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
   */
  public function transform(StorageInterface $storage) {
    // We use a database storage to reduce the memory requirement.
    $mutable = new DatabaseStorage($this->connection, 'config_import');

    // Copy the sync configuration to the created mutable storage.
    self::replaceStorageContents($storage, $mutable);

    // Dispatch the event so that event listeners can alter the configuration.
    // @todo: Use ConfigEvents::STORAGE_TRANSFORM_IMPORT in #2991683
    $this->eventDispatcher->dispatch('config.transform.import', new StorageTransformEvent($mutable));

    // Return the storage with the altered configuration.
    return $mutable;
  }

}
