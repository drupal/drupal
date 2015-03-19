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
 * User picture field migration.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureFieldTest extends MigrateDrupal6TestBase {

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
    $this->assertIdentical('user.user_picture', $field_storage->id());
    $this->assertIdentical(array('user', 'user_picture'), entity_load('migration', 'd6_user_picture_field')->getIdMap()->lookupDestinationID(array('')));
  }

}
