<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a row deletion event for event listeners.
 */
class MigrateRowDeleteEvent extends Event {

  /**
   * Constructs a row deletion event object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration entity.
   * @param array $destinationIdValues
   *   Values represent the destination ID.
   */
  public function __construct(
    protected MigrationInterface $migration,
    protected $destinationIdValues,
  ) {
  }

  /**
   * Gets the migration entity.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The migration being rolled back.
   */
  public function getMigration() {
    return $this->migration;
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
