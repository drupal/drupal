<?php

/**
 * @file
 * Contains \Drupal\migrate\Event\MigrateRollbackEvent.
 */

namespace Drupal\migrate\Event;

use Drupal\migrate\Entity\MigrationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a pre- or post-rollback event for event listeners.
 */
class MigrateRollbackEvent extends Event {

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs an rollback event object.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   Migration entity.
   */
  public function __construct(MigrationInterface $migration) {
    $this->migration = $migration;
  }

  /**
   * Gets the migration entity.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface
   *   The migration entity involved.
   */
  public function getMigration() {
    return $this->migration;
  }

}
