<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade maintenance variables to system.*.yml.
 *
 * @group migrate_drupal_6
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
