<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFileConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to file.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateFileConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_file_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFileSettings() {
    $config = $this->config('file.settings');
    $this->assertIdentical('textfield', $config->get('description.type'));
    $this->assertIdentical(128, $config->get('description.length'));
    $this->assertIdentical('sites/default/files/icons', $config->get('icon.directory'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'file.settings', $config->get());
  }

}
