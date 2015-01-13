<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemCronTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade cron variable to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemCronTest extends MigrateDrupalTestBase {

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
    $this->assertIdentical($config->get('threshold.requirements_warning'), 172800);
    $this->assertIdentical($config->get('threshold.requirements_error'), 1209600);
  }

}
