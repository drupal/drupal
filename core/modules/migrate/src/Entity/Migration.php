<?php

/**
 * @file
 * Contains \Drupal\migrate\Entity\Migration.
 */

namespace Drupal\migrate\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines the Migration entity.
 *
 * The migration entity stores the information about a single migration, like
 * the source, process and destination plugins.
 *
 * @ConfigEntityType(
 *   id = "migration",
 *   label = @Translation("Migration"),
 *   module = "migrate",
 *   handlers = {
 *     "storage" = "Drupal\migrate\MigrationStorage"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   }
 * )
 */
class Migration extends ConfigEntityBase implements MigrationInterface, RequirementsInterface {

  /**
   * The migration ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label for the migration.
   *
   * @var string
   */
  protected $label;

  /**
   * The plugin ID for the row.
   *
   * @var string
   */
  protected $row;

  /**
   * The source configuration, with at least a 'plugin' key.
   *
   * Used to initialize the $sourcePlugin.
   *
   * @var array
   */
  protected $source;

  /**
   * The source plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $sourcePlugin;

  /**
   * The configuration describing the process plugins.
   *
   * This is a strictly internal property and should not returned to calling
   * code, use getProcess() instead.
   *
   * @var array
   */
  protected $process;

  /**
   * The configuration describing the load plugins.
   *
   * @var array
   */
  protected $load;

  /**
   * The cached process plugins.
   *
   * @var array
   */
  protected $processPlugins = [];

  /**
   * The destination configuration, with at least a 'plugin' key.
   *
   * Used to initialize $destinationPlugin.
   *
   * @var array
   */
  protected $destination;

  /**
   * The destination plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateDestinationInterface
   */
  protected $destinationPlugin;

  /**
   * The identifier map data.
   *
   * Used to initialize $idMapPlugin.
   *
   * @var string
   */
  protected $idMap = [];

  /**
   * The identifier map.
   *
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $idMapPlugin;

  /**
   * The source identifiers.
   *
   * An array of source identifiers: the keys are the name of the properties,
   * the values are dependent on the ID map plugin.
   *
   * @var array
   */
  protected $sourceIds = [];

  /**
   * The destination identifiers.
   *
   * An array of destination identifiers: the keys are the name of the
   * properties, the values are dependent on the ID map plugin.
   *
   * @var array
   */
  protected $destinationIds = [];

  /**
   * Information on the high water mark.
   *
   * @var array
   */
  protected $highWaterProperty;

  /**
   * Indicate whether the primary system of record for this migration is the
   * source, or the destination (Drupal). In the source case, migration of
   * an existing object will completely replace the Drupal object with data from
   * the source side. In the destination case, the existing Drupal object will
   * be loaded, then changes from the source applied; also, rollback will not be
   * supported.
   *
   * @var string
   */
  protected $systemOfRecord = self::SOURCE;

  /**
   * Specify value of source_row_status for current map row. Usually set by
   * MigrateFieldHandler implementations.
   *
   * @var int
   */
  protected $sourceRowStatus = MigrateIdMapInterface::STATUS_IMPORTED;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $highWaterStorage;

  /**
   * Track time of last import if TRUE.
   *
   * @var bool
   */
  protected $trackLastImported = FALSE;

  /**
   * These migrations must be already executed before this migration can run.
   *
   * @var array
   */
  protected $requirements = [];

  /**
   * These migrations, if run, must be executed before this migration.
   *
   * These are different from the configuration dependencies. Migration
   * dependencies are only used to store relationships between migrations.
   *
   * The migration_dependencies value is structured like this:
   * @code
   * array(
   *   'required' => array(
   *     // An array of migration IDs that must be run before this migration.
   *   ),
   *   'optional' => array(
   *     // An array of migration IDs that, if they exist, must be run before
   *     // this migration.
   *   ),
   * );
   * @endcode
   *
   * @var array
   */
  protected $migration_dependencies = [];

  /**
   * The migration's configuration dependencies.
   *
   * These store any dependencies on modules or other configuration (including
   * other migrations) that must be available before the migration can be
   * created.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   *
   * @var array
   */
  protected $dependencies = [];

  /**
   * The ID of the template from which this migration was derived, if any.
   *
   * @var string|NULL
   */
  protected $template;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Labels corresponding to each defined status.
   *
   * @var array
   */
  protected $statusLabels = [
    self::STATUS_IDLE => 'Idle',
    self::STATUS_IMPORTING => 'Importing',
    self::STATUS_ROLLING_BACK => 'Rolling back',
    self::STATUS_STOPPING => 'Stopping',
    self::STATUS_DISABLED => 'Disabled',
  ];

  /**
   * {@inheritdoc}
   */
  public function getSourcePlugin() {
    if (!isset($this->sourcePlugin)) {
      $this->sourcePlugin = \Drupal::service('plugin.manager.migrate.source')->createInstance($this->source['plugin'], $this->source, $this);
    }
    return $this->sourcePlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessPlugins(array $process = NULL) {
    if (!isset($process)) {
      $process = $this->process;
    }
    $index = serialize($process);
    if (!isset($this->processPlugins[$index])) {
      $this->processPlugins[$index] = array();
      foreach ($this->getProcessNormalized($process) as $property => $configurations) {
        $this->processPlugins[$index][$property] = array();
        foreach ($configurations as $configuration) {
          if (isset($configuration['source'])) {
            $this->processPlugins[$index][$property][] = \Drupal::service('plugin.manager.migrate.process')->createInstance('get', $configuration, $this);
          }
          // Get is already handled.
          if ($configuration['plugin'] != 'get') {
            $this->processPlugins[$index][$property][] = \Drupal::service('plugin.manager.migrate.process')->createInstance($configuration['plugin'], $configuration, $this);
          }
          if (!$this->processPlugins[$index][$property]) {
            throw new MigrateException("Invalid process configuration for $property");
          }
        }
      }
    }
    return $this->processPlugins[$index];
  }

  /**
   * Resolve shorthands into a list of plugin configurations.
   *
   * @param array $process
   *   A process configuration array.
   *
   * @return array
   *   The normalized process configuration.
   */
  protected function getProcessNormalized(array $process) {
    $normalized_configurations = array();
    foreach ($process as $destination => $configuration) {
      if (is_string($configuration)) {
        $configuration = array(
          'plugin' => 'get',
          'source' => $configuration,
        );
      }
      if (isset($configuration['plugin'])) {
        $configuration = array($configuration);
      }
      $normalized_configurations[$destination] = $configuration;
    }
    return $normalized_configurations;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationPlugin($stub_being_requested = FALSE) {
    if (!isset($this->destinationPlugin)) {
      if ($stub_being_requested && !empty($this->destination['no_stub'])) {
        throw new MigrateSkipRowException;
      }
      $this->destinationPlugin = \Drupal::service('plugin.manager.migrate.destination')->createInstance($this->destination['plugin'], $this->destination, $this);
    }
    return $this->destinationPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdMap() {
    if (!isset($this->idMapPlugin)) {
      $configuration = $this->idMap;
      $plugin = isset($configuration['plugin']) ? $configuration['plugin'] : 'sql';
      $this->idMapPlugin = \Drupal::service('plugin.manager.migrate.id_map')->createInstance($plugin, $configuration, $this);
    }
    return $this->idMapPlugin;
  }

  /**
   * Get the high water storage object.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The storage object.
   */
  protected function getHighWaterStorage() {
    if (!isset($this->highWaterStorage)) {
      $this->highWaterStorage = \Drupal::keyValue('migrate:high_water');
    }
    return $this->highWaterStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighWater() {
    return $this->getHighWaterStorage()->get($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function saveHighWater($high_water) {
    $this->getHighWaterStorage()->set($this->id(), $high_water);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    // Check whether the current migration source and destination plugin
    // requirements are met or not.
    if ($this->getSourcePlugin() instanceof RequirementsInterface) {
      $this->getSourcePlugin()->checkRequirements();
    }
    if ($this->getDestinationPlugin() instanceof RequirementsInterface) {
      $this->getDestinationPlugin()->checkRequirements();
    }

    /** @var \Drupal\migrate\Entity\MigrationInterface[] $required_migrations */
    $required_migrations = $this->getEntityManager()->getStorage('migration')->loadMultiple($this->requirements);

    $missing_migrations = array_diff($this->requirements, array_keys($required_migrations));
    // Check if the dependencies are in good shape.
    foreach ($required_migrations as $migration_id => $required_migration) {
      if (!$required_migration->isComplete()) {
        $missing_migrations[] = $migration_id;
      }
    }
    if ($missing_migrations) {
      throw new RequirementsException('Missing migrations ' . implode(', ', $missing_migrations) . '.', ['requirements' => $missing_migrations]);
    }
  }

  /**
   * Get the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  protected function getEntityManager() {
    if (!isset($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    \Drupal::keyValue('migrate_status')->set($this->id(), $status);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return \Drupal::keyValue('migrate_status')->get($this->id(), static::STATUS_IDLE);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel() {
    $status = $this->getStatus();
    if (isset($this->statusLabels[$status])) {
      return $this->statusLabels[$status];
    }
    else {
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMigrationResult($result) {
    \Drupal::keyValue('migrate_result')->set($this->id(), $result);
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationResult() {
    return \Drupal::keyValue('migrate_result')->get($this->id(), static::RESULT_INCOMPLETE);
  }

  /**
   * {@inheritdoc}
   */
  public function interruptMigration($result) {
    $this->setStatus(MigrationInterface::STATUS_STOPPING);
    $this->setMigrationResult($result);
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete() {
    return $this->getMigrationResult() === static::RESULT_COMPLETED;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    if ($property_name == 'source') {
      // Invalidate the source plugin.
      unset($this->sourcePlugin);
    }
    return parent::set($property_name, $value);
  }


  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    return $this->getProcessNormalized($this->process);
  }

  /**
   * {@inheritdoc}
   */
  public function setProcess(array $process) {
    $this->process = $process;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessOfProperty($property, $process_of_property) {
    $this->process[$property] = $process_of_property;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeProcessOfProperty($property, array $process_of_property) {
    // If we already have a process value then merge the incoming process array
    //otherwise simply set it.
    $current_process = $this->getProcess();
    if (isset($current_process[$property])) {
      $this->process = NestedArray::mergeDeepArray([$current_process, $this->getProcessNormalized([$property => $process_of_property])], TRUE);
    }
    else {
      $this->setProcessOfProperty($property, $process_of_property);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemOfRecord() {
    return $this->systemOfRecord;
  }

  /**
   * {@inheritdoc}
   */
  public function setSystemOfRecord($system_of_record) {
    $this->systemOfRecord = $system_of_record;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTrackLastImported() {
    return $this->trackLastImported;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrackLastImported($track_last_imported) {
    $this->trackLastImported = (bool) $track_last_imported;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationDependencies() {
    return $this->migration_dependencies + ['required' => [], 'optional' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function trustData() {
    // Migrations cannot be trusted since they are often written by hand and not
    // through a UI.
    $this->trustedData = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->calculatePluginDependencies($this->getSourcePlugin());
    $this->calculatePluginDependencies($this->getDestinationPlugin());
    // Add dependencies on required migration dependencies.
    foreach ($this->getMigrationDependencies()['required'] as $dependency) {
      $this->addDependency('config', $this->getEntityType()->getConfigPrefix() . '.' . $dependency);
    }

    return $this->dependencies;
  }
}
