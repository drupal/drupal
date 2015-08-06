<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\d6\MigrateFileConfigsTest.
 */

namespace Drupal\file\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to file.settings.yml.
 *
 * @group file
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
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_file_settings');
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
