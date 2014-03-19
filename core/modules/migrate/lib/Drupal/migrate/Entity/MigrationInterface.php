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

  const SOURCE = 'source';
  const DESTINATION = 'destination';

  /**
   * Codes representing the current status of a migration, and stored in the
   * migrate_status table.
   */
  const STATUS_IDLE = 0;
  const STATUS_IMPORTING = 1;
  const STATUS_ROLLING_BACK = 2;
  const STATUS_STOPPING = 3;
  const STATUS_DISABLED = 4;

  /**
   * Message types to be passed to saveMessage() and saved in message tables.
   * MESSAGE_INFORMATIONAL represents a condition that did not prevent the
   * operation from succeeding - all others represent different severities of
   * conditions resulting in a source record not being imported.
   */
  const MESSAGE_ERROR = 1;
  const MESSAGE_WARNING = 2;
  const MESSAGE_NOTICE = 3;
  const MESSAGE_INFORMATIONAL = 4;

  /**
   * Codes representing the result of a rollback or import process.
   */
  const RESULT_COMPLETED = 1;   // All records have been processed
  const RESULT_INCOMPLETE = 2;  // The process has interrupted itself (e.g., the
                                // memory limit is approaching)
  const RESULT_STOPPED = 3;     // The process was stopped externally (e.g., via
                                // drush migrate-stop)
  const RESULT_FAILED = 4;      // The process had a fatal error
  const RESULT_SKIPPED = 5;     // Dependencies are unfulfilled - skip the process
  const RESULT_DISABLED = 6;    // This migration is disabled, skipping

  /**
   * Returns the initialized source plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface
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
   */
  public function getDestinationPlugin();

  /**
   * Returns the initialized id_map plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  public function getIdMap();

  /**
   * The current value of the highwater mark.
   *
   * The highwater mark defines a timestamp stating the time the import was last
   * run. If the mark is set, only content with a higher timestamp will be
   * imported.
   *
   * @return int
   */
  public function getHighwater();

  /**
   * Save the new highwater mark.
   *
   * @param int $highwater
   *   The highwater timestamp.
   */
  public function saveHighwater($highwater);

}
