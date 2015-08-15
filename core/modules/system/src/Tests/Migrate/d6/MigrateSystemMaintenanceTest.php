<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemMaintenanceTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade maintenance variables to system.*.yml.
 *
 * @group system
 */
class MigrateSystemMaintenanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_system_maintenance');
  }

  /**
   * Tests migration of system (maintenance) variables to system.maintenance.yml.
   */
  public function testSystemMaintenance() {
    $config = $this->config('system.maintenance');
    $this->assertIdentical('Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.', $config->get('message'));
  }

}
