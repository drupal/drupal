<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigImporter.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Config\Context\FreeConfigContext;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a configuration importer.
 *
 * A config importer imports the changes into the configuration system. To
 * determine which changes to import a StorageComparer in used.
 *
 * @see \Drupal\Core\Config\StorageComparerInterface
 *
 * The ConfigImporter has a identifier which is used to construct event names.
 * The events fired during an import are:
 * - 'config.importer.validate': Events listening can throw a
 *   \Drupal\Core\Config\ConfigImporterException to prevent an import from
 *   occurring.
 *   @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
 * - 'config.importer.import': Events listening can react to a successful import.
 *   @see \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber
 *
 * @see \Drupal\Core\Config\ConfigImporterEvent
 */
class ConfigImporter {

  /**
   * The name used to identify events and the lock.
   */
  const ID = 'config.importer';

  /**
   * The storage comparer used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageComparerInterface
   */
  protected $storageComparer;

  /**
   * The event dispatcher used to notify subscribers.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The configuration context.
   *
   * @var \Drupal\Core\Config\Context\ContextInterface
   */
  protected $context;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The plugin manager for entities.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * List of changes processed by the import().
   *
   * @var array
   */
  protected $processed;

  /**
   * Indicates changes to import have been validated.
   *
   * @var bool
   */
  protected $validated;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a configuration import object.
   *
   * @param \Drupal\Core\Config\StorageComparerInterface $storage_comparer
   *   A storage comparer object used to determin configuration changes and
   *   access the source and target storage objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify subscribers of config import events.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory that statically caches config objects.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager used to import config entities.
   * @param \Drupal\Core\Lock\LockBackendInterface
   *   The lock backend to ensure multiple imports do not occur at the same time.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(StorageComparerInterface $storage_comparer, EventDispatcherInterface $event_dispatcher, ConfigFactory $config_factory, EntityManagerInterface $entity_manager, LockBackendInterface $lock, UuidInterface $uuid_service) {
    $this->storageComparer = $storage_comparer;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->lock = $lock;
    $this->uuidService = $uuid_service;
    $this->processed = $this->storageComparer->getEmptyChangelist();
    // Use an override free context for importing so that overrides to do not
    // pollute the imported data. The context is hard coded to ensure this is
    // the case.
    $this->context = new FreeConfigContext($this->eventDispatcher, $this->uuidService);
  }

  /**
   * Gets the configuration storage comparer.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   Storage comparer object used to calculate configuration changes.
   */
  public function getStorageComparer() {
    return $this->storageComparer;
  }

  /**
   * Resets the storage comparer and processed list.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The ConfigImporter instance.
   */
  public function reset() {
    $this->storageComparer->reset();
    $this->processed = $this->storageComparer->getEmptyChangelist();
    $this->validated = FALSE;
    return $this;
  }

  /**
   * Checks if there are any unprocessed changes.
   *
   * @param array $ops
   *   The operations to check for changes. Defaults to all operations, i.e.
   *   array('delete', 'create', 'update').
   *
   * @return bool
   *   TRUE if there are changes to process and FALSE if not.
   */
  public function hasUnprocessedChanges($ops = array('delete', 'create', 'update')) {
    foreach ($ops as $op) {
      if (count($this->getUnprocessed($op))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets list of processed changes.
   *
   * @return array
   *   An array containing a list of processed changes.
   */
  public function getProcessed() {
    return $this->processed;
  }

  /**
   * Sets a change as processed.
   *
   * @param string $op
   *   The change operation performed, either delete, create or update.
   * @param string $name
   *   The name of the configuration processed.
   */
  protected function setProcessed($op, $name) {
    $this->processed[$op][] = $name;
  }

  /**
   * Gets a list of unprocessed changes for a given operation.
   *
   * @param string $op
   *   The change operation to get the unprocessed list for, either delete,
   *   create or update.
   *
   * @return array
   *   An array of configuration names.
   */
  public function getUnprocessed($op) {
    return array_diff($this->storageComparer->getChangelist($op), $this->processed[$op]);
  }

  /**
   * Imports the changelist to the target storage.
   *
   * @throws \Drupal\Core\Config\ConfigException
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The ConfigImporter instance.
   */
  public function import() {
    if ($this->hasUnprocessedChanges()) {
      // Ensure that the changes have been validated.
      $this->validate();

      $this->configFactory->enterContext($this->context);
      if (!$this->lock->acquire(static::ID)) {
        // Another process is synchronizing configuration.
        throw new ConfigImporterException(sprintf('%s is already importing', static::ID));
      }
      $this->importInvokeOwner();
      $this->importConfig();
      // Allow modules to react to a import.
      $this->notify('import');

      // The import is now complete.
      $this->lock->release(static::ID);
      $this->reset();
      // Leave the context used during import and clear the ConfigFactory's
      // static cache.
      $this->configFactory->leaveContext()->reset();
    }
    return $this;
  }

  /**
   * Dispatches validate event for a ConfigImporter object.
   *
   * Events should throw a \Drupal\Core\Config\ConfigImporterException to
   * prevent an import from occurring.
   */
  public function validate() {
    if (!$this->validated) {
      $this->notify('validate');
      $this->validated = TRUE;
    }
    return $this;
  }

  /**
   * Writes an array of config changes from the source to the target storage.
   */
  protected function importConfig() {
    foreach (array('delete', 'create', 'update') as $op) {
      foreach ($this->getUnprocessed($op) as $name) {
        $config = new Config($name, $this->storageComparer->getTargetStorage(), $this->context);
        if ($op == 'delete') {
          $config->delete();
        }
        else {
          $data = $this->storageComparer->getSourceStorage()->read($name);
          $config->setData($data ? $data : array());
          $config->save();
        }
        $this->setProcessed($op, $name);
      }
    }
  }

  /**
   * Invokes import* methods on configuration entity storage controllers.
   *
   * Allow modules to take over configuration change operations for higher-level
   * configuration data.
   *
   * @todo Add support for other extension types; e.g., themes etc.
   */
  protected function importInvokeOwner() {
    // First pass deleted, then new, and lastly changed configuration, in order
    // to handle dependencies correctly.
    foreach (array('delete', 'create', 'update') as $op) {
      foreach ($this->getUnprocessed($op) as $name) {
        // Call to the configuration entity's storage controller to handle the
        // configuration change.
        $handled_by_module = FALSE;
        // Validate the configuration object name before importing it.
        // Config::validateName($name);
        if ($entity_type = config_get_entity_type_by_name($name)) {
          $old_config = new Config($name, $this->storageComparer->getTargetStorage(), $this->context);
          $old_config->load();

          $data = $this->storageComparer->getSourceStorage()->read($name);
          $new_config = new Config($name, $this->storageComparer->getTargetStorage(), $this->context);
          if ($data !== FALSE) {
            $new_config->setData($data);
          }

          $method = 'import' . ucfirst($op);
          $handled_by_module = $this->entityManager->getStorageController($entity_type)->$method($name, $new_config, $old_config);
        }
        if (!empty($handled_by_module)) {
          $this->setProcessed($op, $name);
        }
      }
    }
  }

  /**
   * Dispatches a config importer event.
   *
   * @param string $event_name
   *   The name of the config importer event to dispatch.
   */
  protected function notify($event_name) {
    $this->eventDispatcher->dispatch(static::ID . '.' . $event_name, new ConfigImporterEvent($this));
  }

  /**
   * Determines if a import is already running.
   *
   * @return bool
   *   TRUE if an import is already running, FALSE if not.
   */
  public function alreadyImporting() {
    return !$this->lock->lockMayBeAvailable(static::ID);
  }

  /**
   * Returns the identifier for events and locks.
   *
   * @return string
   *   The identifier for events and locks.
   */
  public function getId() {
    return static::ID;
  }

}
