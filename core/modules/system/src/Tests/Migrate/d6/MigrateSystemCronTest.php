<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemCronTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade cron variable to system.*.yml.
 *
 * @group system
 */
class MigrateSystemCronTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_system_cron');
  }

  /**
   * Tests migration of system (cron) variables to system.cron.yml.
   */
  public function testSystemCron() {
    $config = $this->config('system.cron');
    $this->assertIdentical(172800, $config->get('threshold.requirements_warning'));
    $this->assertIdentical(1209600, $config->get('threshold.requirements_error'));
  }

}
