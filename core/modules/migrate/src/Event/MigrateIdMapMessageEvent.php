<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps an ID map message event for event listeners.
 */
class MigrateIdMapMessageEvent extends Event {

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Array of values uniquely identifying the source row.
   *
   * @var array
   */
  protected $sourceIdValues;

  /**
   * Message to be logged.
   *
   * @var string
   */
  protected $message;

  /**
   * Message severity.
   *
   * @var int
   */
  protected $level;

  /**
   * Constructs a post-save event object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration entity.
   * @param array $source_id_values
   *   Values represent the source ID.
   * @param string $message
   *   The message
   * @param int $level
   *   Severity level (one of the MigrationInterface::MESSAGE_* constants).
   */
  public function __construct(MigrationInterface $migration, array $source_id_values, $message, $level) {
    $this->migration = $migration;
    $this->sourceIdValues = $source_id_values;
    $this->message = $message;
    $this->level = $level;
  }

  /**
   * Gets the migration entity.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The migration entity involved.
   */
  public function getMigration() {
    return $this->migration;
  }

  /**
   * Gets the source ID values.
   *
   * @return array
   *   The source ID as an array.
   */
  public function getSourceIdValues() {
    return $this->sourceIdValues;
  }

  /**
   * Gets the message to be logged.
   *
   * @return string
   *   The message text.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Gets the severity level of the message.
   *
   * Message levels are declared in MigrationInterface and start with MESSAGE_.
   *
   * @see \Drupal\migrate\Plugin\MigrationInterface
   *
   * @return int
   *   The message level.
   */
  public function getLevel() {
    return $this->level;
  }

}
