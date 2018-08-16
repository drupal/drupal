<?php

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\file\Entity\File;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates all files in the file_managed table.
 *
 * @group file
 */
class MigrateFileTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fileMigrationSetup();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    return [
      'path' => 'public://sites/default/files/cube.jpeg',
      'size' => '3620',
      'base_path' => 'public://',
      'plugin_id' => 'd7_file',
    ];
  }

  /**
   * Tests that all expected files are migrated.
   */
  public function testFileMigration() {
    $this->assertEntity(1, 'cube.jpeg', 'public://cube.jpeg', 'image/jpeg', '3620', '1421727515', '1421727515', '1');
    // Ensure temporary file was not migrated.
    $this->assertNull(File::load(4));
  }

}
