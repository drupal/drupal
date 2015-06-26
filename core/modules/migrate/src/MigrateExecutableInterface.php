<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateExecutableInterface.
 */

namespace Drupal\migrate;

use Drupal\migrate\Entity\MigrationInterface;

interface MigrateExecutableInterface {

  /**
   * Performs an import operation - migrate items from source to destination.
   */
  public function import();

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
   *   \Drupal\migrate\Plugin\migrate\process\Iterator::transformKey for an
   *   example.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function processRow(Row $row, array $process = NULL, $value = NULL);

  /**
   * Returns the time limit.
   *
   * @return null|int
   *   The time limit, NULL if no limit or if the units were not in seconds.
   */
  public function getTimeLimit();

  /**
   * Passes messages through to the map class.
   *
   * @param string $message
   *   The message to record.
   * @param int $level
   *   (optional) Message severity (defaults to MESSAGE_ERROR).
   */
  public function saveMessage($message, $level = MigrationInterface::MESSAGE_ERROR);

  /**
   * Queues messages to be later saved through the map class.
   *
   * @param string $message
   *   The message to record.
   * @param int $level
   *   (optional) Message severity (defaults to MESSAGE_ERROR).
   */
  public function queueMessage($message, $level = MigrationInterface::MESSAGE_ERROR);

  /**
   * Saves any messages we've queued up to the message table.
   */
  public function saveQueuedMessages();
}
