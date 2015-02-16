<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Uploads migration.
 *
 * @group migrate_drupal
 */
class MigrateUploadFieldTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('file', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_upload_field');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 field migration.
   */
  public function testUpload() {
    $field_storage = FieldStorageConfig::load('node.upload');
    $this->assertIdentical($field_storage->id(), 'node.upload');
    $this->assertIdentical(array('node', 'upload'), entity_load('migration', 'd6_upload_field')->getIdMap()->lookupDestinationID(array('')));
  }

}
