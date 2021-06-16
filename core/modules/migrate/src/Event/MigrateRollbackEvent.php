<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a pre- or post-rollback event for event listeners.
 */
class MigrateRollbackEvent extends Event {

  /**
   * Migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a rollback event object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration entity.
   */
  public function __construct(MigrationInterface $migration) {
    $this->migration = $migration;
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
