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
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (file) variables to system.file.yml.
   */
  public function testSystemFile() {
    $config = \Drupal::configFactory()->getEditable('system.file');
    $this->assertIdentical($config->get('path.temporary'), 'files/temp');
    $this->assertIdentical($config->get('allow_insecure_uploads'), TRUE);
  }

}
