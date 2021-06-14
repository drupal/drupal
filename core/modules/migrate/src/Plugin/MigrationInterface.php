<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for migrations.
 */
interface MigrationInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * The migration is currently not running.
   */
  const STATUS_IDLE = 0;

  /**
   * The migration is currently importing.
   */
  const STATUS_IMPORTING = 1;

  /**
   * The migration is currently being rolled back.
   */
  const STATUS_ROLLING_BACK = 2;

  /**
   * The migration is being stopped.
   */
  const STATUS_STOPPING = 3;

  /**
   * The migration has been disabled.
   */
  const STATUS_DISABLED = 4;

  /**
   * Migration error.
   */
  const MESSAGE_ERROR = 1;

  /**
   * Migration warning.
   */
  const MESSAGE_WARNING = 2;

  /**
   * Migration notice.
   */
  const MESSAGE_NOTICE = 3;

  /**
   * Migration info.
   */
  const MESSAGE_INFORMATIONAL = 4;

  /**
   * All records have been processed.
   */
  const RESULT_COMPLETED = 1;

  /**
   * The process has stopped itself (e.g., the memory limit is approaching).
   */
  const RESULT_INCOMPLETE = 2;

  /**
   * The process was stopped externally (e.g., via drush migrate-stop).
   */
  const RESULT_STOPPED = 3;

  /**
   * The process had a fatal error.
   */
  const RESULT_FAILED = 4;

  /**
   * Dependencies are unfulfilled - skip the process.
   */
  const RESULT_SKIPPED = 5;

  /**
   * This migration is disabled, skipping.
   */
  const RESULT_DISABLED = 6;

  /**
   * An alias for getPluginId() for backwards compatibility reasons.
   *
   * @return string
   *   The plugin_id of the plugin instance.
   *
   * @see \Drupal\migrate\Plugin\MigrationInterface::getPluginId()
   */
  public function id();

  /**
   * Get the plugin label.
   *
   * @return string
   *   The label for this migration.
   */
  public function label();

  /**
   * Get a list of required plugin IDs.
   *
   * @returns string[]
   */
  public function getRequirements(): array;

  /**
   * Returns the initialized source plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface
   *   The source plugin.
   */
  public function getSourcePlugin();

  /**
   * Returns the process plugins.
   *
   * @param array $process
   *   A process configuration array.
   *
   * @return \Drupal\migrate\Plugin\MigrateProcessInterface[][]
   *   An associative array. The keys are the destination property names. Values
   *   are process pipelines. Each pipeline contains an array of plugins.
   */
  public function getProcessPlugins(array $process = NULL);

  /**
   * Returns the initialized destination plugin.
   *
   * @param bool $stub_being_requested
   *   TRUE to indicate that this destination will be asked to construct a stub.
   *
   * @return \Drupal\migrate\Plugin\MigrateDestinationInterface
   *   The destination plugin.
   */
  public function getDestinationPlugin($stub_being_requested = FALSE);

  /**
   * Returns the initialized id_map plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The ID map.
   */
  public function getIdMap();

  /**
   * Check if all source rows from this migration have been processed.
   *
   * @return bool
   *   TRUE if this migration is complete otherwise FALSE.
   */
  public function allRowsProcessed();

  /**
   * Set the current migration status.
   *
   * @param int $status
   *   One of the STATUS_* constants.
   */
  public function setStatus($status);

  /**
   * Get the current migration status.
   *
   * @return int
   *   The current migration status. Defaults to STATUS_IDLE.
   */
  public function getStatus();

  /**
   * Retrieve a label for the current status.
   *
   * @return string
   *   User-friendly string corresponding to a STATUS_ constant.
   */
  public function getStatusLabel();

  /**
   * Get the result to return upon interruption.
   *
   * @return int
   *   The current interruption result. Defaults to RESULT_INCOMPLETE.
   */
  public function getInterruptionResult();

  /**
   * Clears the result to return upon interruption.
   */
  public function clearInterruptionResult();

  /**
   * Signal that the migration should be interrupted with the specified result
   * code.
   *
   * @param int $result
   *   One of the MigrationInterface::RESULT_* constants.
   */
  public function interruptMigration($result);

  /**
   * Get the normalized process pipeline configuration describing the process
   * plugins.
   *
   * The process configuration is always normalized. All shorthand processing
   * will be expanded into their full representations.
   *
   * @see https://www.drupal.org/node/2129651#get-shorthand
   *
   * @return array
   *   The normalized configuration describing the process plugins.
   */
  public function getProcess();

  /**
   * Allows you to override the entire process configuration.
   *
   * @param array $process
   *   The entire process pipeline configuration describing the process plugins.
   *
   * @return $this
   */
  public function setProcess(array $process);

  /**
   * Set the process pipeline configuration for an individual destination field.
   *
   * This method allows you to set the process pipeline configuration for a
   * single property within the full process pipeline configuration.
   *
   * @param string $property
   *   The property of which to set the process pipeline configuration.
   * @param mixed $process_of_property
   *   The process pipeline configuration to be set for this property.
   *
   * @return $this
   *   The migration entity.
   */
  public function setProcessOfProperty($property, $process_of_property);

  /**
   * Merge the process pipeline configuration for a single property.
   *
   * @param string $property
   *   The property of which to merge the passed in process pipeline
   *   configuration.
   * @param array $process_of_property
   *   The process pipeline configuration to be merged with the existing process
   *   pipeline configuration.
   *
   * @return $this
   *   The migration entity.
   */
  public function mergeProcessOfProperty($property, array $process_of_property);

  /**
   * Checks if the migration should track time of last import.
   *
   * @return bool
   *   TRUE if the migration is tracking last import time.
   */
  public function isTrackLastImported();

  /**
   * Set if the migration should track time of last import.
   *
   * @param bool $track_last_imported
   *   Boolean value to indicate if the migration should track last import time.
   *
   * @return $this
   */
  public function setTrackLastImported($track_last_imported);

  /**
   * Get the dependencies for this migration.
   *
   * @return array
   *   The dependencies for this migrations.
   */
  public function getMigrationDependencies();

  /**
   * Get the destination configuration, with at least a 'plugin' key.
   *
   * @return array
   *   The destination configuration.
   */
  public function getDestinationConfiguration();

  /**
   * Get the source configuration, with at least a 'plugin' key.
   *
   * @return array
   *   The source configuration.
   */
  public function getSourceConfiguration();

  /**
   * If true, track time of last import.
   *
   * @return bool
   *   Flag to determine desire of tracking time of last import.
   */
  public function getTrackLastImported();

  /**
   * The destination identifiers.
   *
   * An array of destination identifiers: the keys are the name of the
   * properties, the values are dependent on the ID map plugin.
   *
   * @return array
   *   Destination identifiers.
   */
  public function getDestinationIds();

  /**
   * The migration tags.
   *
   * @return array
   *   Migration tags.
   */
  public function getMigrationTags();

  /**
   * Indicates if the migration is auditable.
   *
   * @return bool
   */
  public function isAuditable();

}
