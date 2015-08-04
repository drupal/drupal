<?php

/**
 * @file
 * Contains \Drupal\migrate\Event\MigratePostRowSaveEvent.
 */

namespace Drupal\migrate\Event;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Wraps a post-save event for event listeners.
 */
class MigratePostRowSaveEvent extends MigratePreRowSaveEvent {

  /**
   * Constructs a post-save event object.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   Migration entity.
   * @param \Drupal\migrate\Row $row
   *   Row object.
   * @param array|bool $destination_id_values
   *   Values represent the destination ID.
   */
  public function __construct(MigrationInterface $migration, Row $row, $destination_id_values) {
    parent::__construct($migration, $row);
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
