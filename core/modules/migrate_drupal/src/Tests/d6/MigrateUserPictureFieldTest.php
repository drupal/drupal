<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * User picture field migration.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureFieldTest extends MigrateDrupalTestBase {

  static $modules = array('image');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_user_picture_field');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the user picture field migration.
   */
  public function testUserPictureField() {
    $field_storage = FieldStorageConfig::load('user.user_picture');
    $this->assertIdentical($field_storage->id(), 'user.user_picture');
    $this->assertIdentical(array('user', 'user_picture'), entity_load('migration', 'd6_user_picture_field')->getIdMap()->lookupDestinationID(array('')));
  }

}
