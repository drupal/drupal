<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\d6\MigrateUploadFieldTest.
 */

namespace Drupal\file\Tests\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Uploads migration.
 *
 * @group migrate_drupal_6
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
    $this->executeMigration('d6_upload_field');
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 field migration.
   */
  public function testUpload() {
    $field_storage = FieldStorageConfig::load('node.upload');
    $this->assertIdentical('node.upload', $field_storage->id());
    $this->assertIdentical(array('node', 'upload'), entity_load('migration', 'd6_upload_field')->getIdMap()->lookupDestinationID(array('')));
  }

}
