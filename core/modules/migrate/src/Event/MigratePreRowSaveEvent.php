<?php

/**
 * @file
 * Contains \Drupal\migrate\Event\MigratePreSaveEvent.
 */

namespace Drupal\migrate\Event;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a pre-save event for event listeners.
 */
class MigratePreRowSaveEvent extends Event {

  /**
   * Row object.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a pre-save event object.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   Migration entity.
   */
  public function __construct(MigrationInterface $migration, Row $row) {
    $this->migration = $migration;
    $this->row = $row;
  }

  /**
   * Gets the migration entity.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface
   *   The migration entity being imported.
   */
  public function getMigration() {
    return $this->migration;
  }

  /**
   * Gets the row object.
   *
   * @return \Drupal\migrate\Row
   *   The row object about to be imported.
   */
  public function getRow() {
    return $this->row;
  }

}
