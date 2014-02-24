<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigImporter.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\Lock\LockBackendInterface;
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
 * - ConfigEvents::IMPORT_VALIDATE: Events listening can throw a
 *   \Drupal\Core\Config\ConfigImporterException to prevent an import from
 *   occurring.
 *   @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
 * - ConfigEvents::IMPORT: Events listening can react to a successful import.
 *   @see \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber
 *
 * @see \Drupal\Core\Config\ConfigImporterEvent
 */
class ConfigImporter extends DependencySerialization {

  /**
   * The name used to identify the lock.
   */
  const LOCK_ID = 'config_importer';

  /**
   * The storage comparer used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageComparerInterface
   */
  protected $storageComparer;

  /**
   * The event dispatcher used to notify subscribers.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

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
   * Constructs a configuration import object.
   *
   * @param \Drupal\Core\Config\StorageComparerInterface $storage_comparer
   *   A storage comparer object used to determin configuration changes and
   *   access the source and target storage objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify subscribers of config import events.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Lock\LockBackendInterface
   *   The lock backend to ensure multiple imports do not occur at the same time.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config
   *   The typed configuration manager.
   */
  public function __construct(StorageComparerInterface $storage_comparer, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, LockBackendInterface $lock, TypedConfigManager $typed_config) {
    $this->storageComparer = $storage_comparer;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->lock = $lock;
    $this->typedConfigManager = $typed_config;
    $this->processed = $this->storageComparer->getEmptyChangelist();
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

      if (!$this->lock->acquire(static::LOCK_ID)) {
        // Another process is synchronizing configuration.
        throw new ConfigImporterException(sprintf('%s is already importing', static::LOCK_ID));
      }
      // First pass deleted, then new, and lastly changed configuration, in order
      // to handle dependencies correctly.
      // @todo Implement proper dependency ordering using
      //   https://drupal.org/node/2080823
      foreach (array('delete', 'create', 'update') as $op) {
        foreach ($this->getUnprocessed($op) as $name) {
          $this->process($op, $name);
        }
      }
      // Allow modules to react to a import.
      $this->eventDispatcher->dispatch(ConfigEvents::IMPORT, new ConfigImporterEvent($this));


      // The import is now complete.
      $this->lock->release(static::LOCK_ID);
      $this->reset();
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
      if (!$this->storageComparer->validateSiteUuid()) {
        throw new ConfigImporterException('Site UUID in source storage does not match the target storage.');
      }
      $this->eventDispatcher->dispatch(ConfigEvents::IMPORT_VALIDATE, new ConfigImporterEvent($this));
      $this->validated = TRUE;
    }
    return $this;
  }

  /**
   * Processes a configuration change.
   *
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   */
  protected function process($op, $name) {
    if (!$this->importInvokeOwner($op, $name)) {
      $this->importConfig($op, $name);
    }
  }

  /**
   * Writes a configuration change from the source to the target storage.
   *
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   */
  protected function importConfig($op, $name) {
    $config = new Config($name, $this->storageComparer->getTargetStorage(), $this->eventDispatcher, $this->typedConfigManager);
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

  /**
   * Invokes import* methods on configuration entity storage controllers.
   *
   * Allow modules to take over configuration change operations for higher-level
   * configuration data.
   *
   * @todo Add support for other extension types; e.g., themes etc.
   *
   * @param string $op
   *   The change operation to get the unprocessed list for, either delete,
   *   create or update.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @return bool
   *   TRUE if the configuration was imported as a configuration entity. FALSE
   *   otherwise.
   */
  protected function importInvokeOwner($op, $name) {
    // Call to the configuration entity's storage controller to handle the
    // configuration change.
    $handled_by_module = FALSE;
    // Validate the configuration object name before importing it.
    // Config::validateName($name);
    if ($entity_type = $this->configManager->getEntityTypeIdByName($name)) {
      $old_config = new Config($name, $this->storageComparer->getTargetStorage(), $this->eventDispatcher, $this->typedConfigManager);
      if ($old_data = $this->storageComparer->getTargetStorage()->read($name)) {
        $old_config->initWithData($old_data);
      }

      $data = $this->storageComparer->getSourceStorage()->read($name);
      $new_config = new Config($name, $this->storageComparer->getTargetStorage(), $this->eventDispatcher, $this->typedConfigManager);
      if ($data !== FALSE) {
        $new_config->setData($data);
      }

      $method = 'import' . ucfirst($op);
      $handled_by_module = $this->configManager->getEntityManager()->getStorageController($entity_type)->$method($name, $new_config, $old_config);
    }
    if (!empty($handled_by_module)) {
      $this->setProcessed($op, $name);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determines if a import is already running.
   *
   * @return bool
   *   TRUE if an import is already running, FALSE if not.
   */
  public function alreadyImporting() {
    return !$this->lock->lockMayBeAvailable(static::LOCK_ID);
  }

}
