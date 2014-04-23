<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Test the user_picture field migration.
 */
class MigrateUserPictureFieldTest extends MigrateDrupalTestBase {

  static $modules = array('image');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate user picture field',
      'description'  => 'User picture field migration',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_user_picture_field');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the user picture field migration.
   */
  public function testUserPictureField() {
    $field = entity_load('field_config', 'user.user_picture');
    $this->assertEqual($field->id(), 'user.user_picture');
    $this->assertEqual(array('user', 'user_picture'), entity_load('migration', 'd6_user_picture_field')->getIdMap()->lookupDestinationID(array('')));
  }

}
