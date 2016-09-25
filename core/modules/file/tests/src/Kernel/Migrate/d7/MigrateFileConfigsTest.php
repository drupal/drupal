<?php

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to file.settings.yml.
 *
 * @group migrate_drupal_7
 */
class MigrateFileConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('file_settings');
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFileSettings() {
    $config = $this->config('file.settings');
    $this->assertSame('textfield', $config->get('description.type'));
    $this->assertSame(256, $config->get('description.length'));
    $this->assertSame('sites/default/files/icons', $config->get('icon.directory'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'file.settings', $config->get());
  }

}
