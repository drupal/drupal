<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemCronTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Upgrade cron variable to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemCronTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
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
