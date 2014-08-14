<?php

/**
 * @file
 * Contains \Drupal\migrate\Entity\MigrationInterface.
 */

namespace Drupal\migrate\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for migrations.
 */
interface MigrationInterface extends ConfigEntityInterface {

  /**
   * A constant used for systemOfRecord.
   */
  const SOURCE = 'source';

  /**
   * A constant used for systemOfRecord.
   */
  const DESTINATION = 'destination';

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
   * @return \Drupal\migrate\Plugin\MigrateDestinationInterface
   *   The destination plugin.
   */
  public function getDestinationPlugin();

  /**
   * Returns the initialized id_map plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The ID map.
   */
  public function getIdMap();

  /**
   * The current value of the high water mark.
   *
   * The high water mark defines a timestamp stating the time the import was last
   * run. If the mark is set, only content with a higher timestamp will be
   * imported.
   *
   * @return int
   *   A Unix timestamp representing the high water mark.
   */
  public function getHighWater();

  /**
   * Save the new high water mark.
   *
   * @param int $high_water
   *   The high water timestamp.
   */
  public function saveHighWater($high_water);

  /**
   * Check if this migration is complete.
   *
   * @return bool
   *   TRUE if this migration is complete otherwise FALSE.
   */
  public function isComplete();

  /**
   * Set the migration result.
   *
   * @param int $result
   *   One of the RESULT_* constants.
   */
  public function setMigrationResult($result);

  /**
   * Get the current migration result.
   *
   * @return int
   *   The current migration result. Defaults to RESULT_INCOMPLETE.
   */
  public function getMigrationResult();

}
