<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemMaintenanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade maintenance variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemMaintenanceTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_maintenance');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (maintenance) variables to system.maintenance.yml.
   */
  public function testSystemMaintenance() {
    $config = $this->config('system.maintenance');
    $this->assertIdentical($config->get('message'), 'Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.');
  }

}
