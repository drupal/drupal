<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemCronTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

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
    $migration = entity_load('migration', 'd6_system_cron');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
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
