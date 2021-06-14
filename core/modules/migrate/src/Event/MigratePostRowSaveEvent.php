<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Row;

/**
 * Wraps a post-save event for event listeners.
 */
class MigratePostRowSaveEvent extends MigratePreRowSaveEvent {

  /**
   * The row's destination ID.
   *
   * @var array|bool
   */
  protected $destinationIdValues = [];

  /**
   * Constructs a post-save event object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration entity.
   * @param \Drupal\migrate\MigrateMessageInterface $message
   *   The message interface.
   * @param \Drupal\migrate\Row $row
   *   Row object.
   * @param array|bool $destination_id_values
   *   Values represent the destination ID.
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message, Row $row, $destination_id_values) {
    parent::__construct($migration, $message, $row);
    $this->destinationIdValues = $destination_id_values;
  }

  /**
   * Gets the destination ID values.
   *
   * @return array
   *   The destination ID as an array.
   */
  public function getDestinationIdValues() {
    return $this->destinationIdValues;
  }

}
