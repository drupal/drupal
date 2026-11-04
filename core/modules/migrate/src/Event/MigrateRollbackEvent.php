<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a pre- or post-rollback event for event listeners.
 */
class MigrateRollbackEvent extends Event {

  /**
   * Constructs a rollback event object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration entity.
   */
  public function __construct(
    protected MigrationInterface $migration,
  ) {
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

}
