<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\MigrateStatusTest
 */

namespace Drupal\migrate\Tests;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Test migration status tracking.
 *
 * @group migrate
 */
class MigrateStatusTest extends MigrateTestBase {

  /**
   * Test different connection types.
   */
  public function testStatus() {
    // Create a minimally valid migration.
    $configuration = [
      'id' => 'migration_status_test',
      'migration_tags' => ['Testing'],
      'source' => ['plugin' => 'empty'],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'migrate_test.settings',
      ],
      'process' => ['foo' => 'bar'],
    ];
    $migration = Migration::create($configuration);
    $migration->save();

    // Default status is idle.
    $status = $migration->getStatus();
    $this->assertIdentical($status, MigrationInterface::STATUS_IDLE);

    // Test setting and retrieving all known status values.
    $status_list = array(
      MigrationInterface::STATUS_IDLE,
      MigrationInterface::STATUS_IMPORTING,
      MigrationInterface::STATUS_ROLLING_BACK,
      MigrationInterface::STATUS_STOPPING,
      MigrationInterface::STATUS_DISABLED,
    );
    foreach ($status_list as $status) {
      $migration->setStatus($status);
      $this->assertIdentical($migration->getStatus(), $status);
    }
  }

}
