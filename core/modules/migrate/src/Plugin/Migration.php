<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Migration plugin.
 *
 * The migration process plugin represents one single migration and acts like a
 * container for the information about a single migration such as the source,
 * process and destination plugins.
 */
class Migration extends PluginBase implements MigrationInterface, RequirementsInterface, ContainerFactoryPluginInterface {

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
  protected $process = [];

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
   * Specify value of source_row_status for current map row. Usually set by
   * MigrateFieldHandler implementations.
   *
   * @var int
   */
  protected $sourceRowStatus = MigrateIdMapInterface::STATUS_IMPORTED;

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
   * An optional list of tags, used by the plugin manager for filtering.
   *
   * @var array
   */
  protected $migration_tags = [];

  /**
   * Whether the migration is auditable.
   *
   * If set to TRUE, the migration's IDs will be audited. This means that, if
   * the highest destination ID is greater than the highest source ID, a warning
   * will be displayed that entities might be overwritten.
   *
   * @var bool
   */
  protected $audit = FALSE;

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
   * The migration plugin manager for loading other migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The source plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $sourcePluginManager;

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The destination plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrateDestinationPluginManager
   */
  protected $destinationPluginManager;

  /**
   * The ID map plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $idMapPluginManager;

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
   * Constructs a Migration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $source_plugin_manager
   *   The source migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager
   *   The process migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigrateDestinationPluginManager $destination_plugin_manager
   *   The destination migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $idmap_plugin_manager
   *   The ID map migration plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $source_plugin_manager, MigratePluginManagerInterface $process_plugin_manager, MigrateDestinationPluginManager $destination_plugin_manager, MigratePluginManagerInterface $idmap_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->sourcePluginManager = $source_plugin_manager;
    $this->processPluginManager = $process_plugin_manager;
    $this->destinationPluginManager = $destination_plugin_manager;
    $this->idMapPluginManager = $idmap_plugin_manager;

    foreach (NestedArray::mergeDeepArray([$plugin_definition, $configuration], TRUE) as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.source'),
      $container->get('plugin.manager.migrate.process'),
      $container->get('plugin.manager.migrate.destination'),
      $container->get('plugin.manager.migrate.id_map')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * Retrieves the ID map plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The ID map plugin.
   */
  public function getIdMapPlugin() {
    return $this->idMapPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePlugin() {
    if (!isset($this->sourcePlugin)) {
      $this->sourcePlugin = $this->sourcePluginManager->createInstance($this->source['plugin'], $this->source, $this);
    }
    return $this->sourcePlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessPlugins(array $process = NULL) {
    if (!isset($process)) {
      $process = $this->getProcess();
    }
    $index = serialize($process);
    if (!isset($this->processPlugins[$index])) {
      $this->processPlugins[$index] = [];
      foreach ($this->getProcessNormalized($process) as $property => $configurations) {
        $this->processPlugins[$index][$property] = [];
        if (!is_array($configurations) && !$this->processPlugins[$index][$property]) {
          throw new MigrateException(sprintf("Process configuration for '$property' must be an array", $property));
        }
        foreach ($configurations as $configuration) {
          if (isset($configuration['source'])) {
            $this->processPlugins[$index][$property][] = $this->processPluginManager->createInstance('get', $configuration, $this);
          }
          // Get is already handled.
          if ($configuration['plugin'] != 'get') {
            $this->processPlugins[$index][$property][] = $this->processPluginManager->createInstance($configuration['plugin'], $configuration, $this);
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
    $normalized_configurations = [];
    foreach ($process as $destination => $configuration) {
      if (is_string($configuration)) {
        $configuration = [
          'plugin' => 'get',
          'source' => $configuration,
        ];
      }
      if (isset($configuration['plugin'])) {
        $configuration = [$configuration];
      }
      if (!is_array($configuration)) {
        $migration_id = $this->getPluginId();
        throw new MigrateException("Invalid process for destination '$destination' in migration '$migration_id'");
      }
      $normalized_configurations[$destination] = $configuration;
    }
    return $normalized_configurations;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationPlugin($stub_being_requested = FALSE) {
    if ($stub_being_requested && !empty($this->destination['no_stub'])) {
      throw new MigrateSkipRowException('Stub requested but not made because no_stub configuration is set.');
    }
    if (!isset($this->destinationPlugin)) {
      $this->destinationPlugin = $this->destinationPluginManager->createInstance($this->destination['plugin'], $this->destination, $this);
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
      $this->idMapPlugin = $this->idMapPluginManager->createInstance($plugin, $configuration, $this);
    }
    return $this->idMapPlugin;
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

    if (empty($this->requirements)) {
      // There are no requirements to check.
      return;
    }
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $required_migrations */
    $required_migrations = $this->getMigrationPluginManager()->createInstances($this->requirements);

    $missing_migrations = array_diff($this->requirements, array_keys($required_migrations));
    // Check if the dependencies are in good shape.
    foreach ($required_migrations as $migration_id => $required_migration) {
      if (!$required_migration->allRowsProcessed()) {
        $missing_migrations[] = $migration_id;
      }
    }
    if ($missing_migrations) {
      throw new RequirementsException('Missing migrations ' . implode(', ', $missing_migrations) . '.', ['requirements' => $missing_migrations]);
    }
  }

  /**
   * Gets the migration plugin manager.
   *
   * @return \Drupal\migrate\Plugin\MigratePluginManager
   *   The plugin manager.
   */
  protected function getMigrationPluginManager() {
    return $this->migrationPluginManager;
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
  public function getInterruptionResult() {
    return \Drupal::keyValue('migrate_interruption_result')->get($this->id(), static::RESULT_INCOMPLETE);
  }

  /**
   * {@inheritdoc}
   */
  public function clearInterruptionResult() {
    \Drupal::keyValue('migrate_interruption_result')->delete($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function interruptMigration($result) {
    $this->setStatus(MigrationInterface::STATUS_STOPPING);
    \Drupal::keyValue('migrate_interruption_result')->set($this->id(), $result);
  }

  /**
   * {@inheritdoc}
   */
  public function allRowsProcessed() {
    $source_count = $this->getSourcePlugin()->count();
    // If the source is uncountable, we have no way of knowing if it's
    // complete, so stipulate that it is.
    if ($source_count < 0) {
      return TRUE;
    }
    $processed_count = $this->getIdMap()->processedCount();
    // We don't use == because in some circumstances (like unresolved stubs
    // being created), the processed count may be higher than the available
    // source rows.
    return $source_count <= $processed_count;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    if ($property_name == 'source') {
      // Invalidate the source plugin.
      unset($this->sourcePlugin);
    }
    elseif ($property_name === 'destination') {
      // Invalidate the destination plugin.
      unset($this->destinationPlugin);
    }
    $this->{$property_name} = $value;
    return $this;
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
    // otherwise simply set it.
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
    $this->migration_dependencies = ($this->migration_dependencies ?: []) + ['required' => [], 'optional' => []];
    if (count($this->migration_dependencies) !== 2 || !is_array($this->migration_dependencies['required']) || !is_array($this->migration_dependencies['optional'])) {
      throw new InvalidPluginDefinitionException($this->id(), "Invalid migration dependencies configuration for migration {$this->id()}");
    }
    $this->migration_dependencies['optional'] = array_unique(array_merge($this->migration_dependencies['optional'], $this->findMigrationDependencies($this->process)));
    return $this->migration_dependencies;
  }

  /**
   * Find migration dependencies from migration_lookup and sub_process plugins.
   *
   * @param array $process
   *   A process configuration array.
   *
   * @return array
   *   The migration dependencies.
   */
  protected function findMigrationDependencies($process) {
    $return = [];
    foreach ($this->getProcessNormalized($process) as $process_pipeline) {
      foreach ($process_pipeline as $plugin_configuration) {
        if (in_array($plugin_configuration['plugin'], ['migration', 'migration_lookup'], TRUE)) {
          $return = array_merge($return, (array) $plugin_configuration['migration']);
        }
        if (in_array($plugin_configuration['plugin'], ['iterator', 'sub_process'], TRUE)) {
          $return = array_merge($return, $this->findMigrationDependencies($plugin_configuration['process']));
        }
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = [];
    // While normal plugins do not change their definitions on the fly, this
    // one does so accommodate for that.
    foreach (parent::getPluginDefinition() as $key => $value) {
      $definition[$key] = isset($this->$key) ? $this->$key : $value;
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationConfiguration() {
    return $this->destination;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceConfiguration() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackLastImported() {
    return $this->trackLastImported;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationIds() {
    return $this->destinationIds;
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationTags() {
    return $this->migration_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuditable() {
    return (bool) $this->audit;
  }

}
