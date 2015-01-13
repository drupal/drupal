<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFileConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to file.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateFileConfigsTest extends MigrateDrupalTestBase {

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
    $this->assertIdentical($config->get('description.type'), 'textfield');
    $this->assertIdentical($config->get('description.length'), 128);
    $this->assertIdentical($config->get('icon.directory'), 'sites/default/files/icons');
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'file.settings', $config->get());
  }

}
