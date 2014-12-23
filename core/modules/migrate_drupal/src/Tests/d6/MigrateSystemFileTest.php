<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemFileTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemFileTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_file');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemFile.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (file) variables to system.file.yml.
   */
  public function testSystemFile() {
    $old_state = \Drupal::configFactory()->getOverrideState();
    \Drupal::configFactory()->setOverrideState(FALSE);
    $config = $this->config('system.file');
    $this->assertIdentical($config->get('path.temporary'), 'files/temp');
    $this->assertIdentical($config->get('allow_insecure_uploads'), TRUE);
    \Drupal::configFactory()->setOverrideState($old_state);
  }

}
