<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\migrate\MigrateExecutable;

/**
 * Migrates and rolls back Drupal 7 view modes.
 *
 * @group field
 */
class RollbackViewModesTest extends MigrateViewModesTest {

  /**
   * Tests migrating D7 view modes, then rolling back.
   */
  public function testMigration() {
    // Test that the view modes have migrated (prior to rollback).
    parent::testMigration();

    $this->executeRollback('d7_view_modes');

    // Check that view modes have been rolled back.
    $view_mode_ids = [
      'comment.full',
      'node.teaser',
      'node.full',
      'user.full',
    ];
    foreach ($view_mode_ids as $view_mode_id) {
      $this->assertNull(EntityViewMode::load($view_mode_id));
    }
  }

  /**
   * Executes a single rollback.
   *
   * @param string|\Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to rollback, or its ID.
   */
  protected function executeRollback($migration) {
    if (is_string($migration)) {
      $this->migration = $this->getMigration($migration);
    }
    else {
      $this->migration = $migration;
    }
    (new MigrateExecutable($this->migration, $this))->rollback();
  }

}
