<?php

namespace Drupal\migrate;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Interface for the migration executable.
 */
interface MigrateExecutableInterface {

  /**
   * Performs an import operation - migrate items from source to destination.
   *
   * @return int
   *   Returns a value indicating the status of the import operation.
   *   The possible values are the 'RESULT_' constants defined
   *   in MigrationInterface.
   *
   * @see \Drupal\migrate\Plugin\MigrationInterface
   */
  public function import();

  /**
   * Performs a rollback operation - remove previously-imported items.
   */
  public function rollback();

  /**
   * Processes a row.
   *
   * @param \Drupal\migrate\Row $row
   *   The $row to be processed.
   * @param array $process
   *   (optional) A process pipeline configuration. If not set, the top level
   *   process configuration in the migration entity is used.
   * @param mixed $value
   *   (optional) Initial value of the pipeline for the first destination.
   *   Usually setting this is not necessary as $process typically starts with
   *   a 'get'. This is useful only when the $process contains a single
   *   destination and needs to access a value outside of the source. See
   *   \Drupal\migrate\Plugin\migrate\process\SubProcess::transformKey for an
   *   example.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function processRow(Row $row, ?array $process = NULL, $value = NULL);

  /**
   * Passes messages through to the map class.
   *
   * @param string $message
   *   The message to record.
   * @param int $level
   *   (optional) Message severity (defaults to MESSAGE_ERROR).
   */
  public function saveMessage($message, $level = MigrationInterface::MESSAGE_ERROR);

}
