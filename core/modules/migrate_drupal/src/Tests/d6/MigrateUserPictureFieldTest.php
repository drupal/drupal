<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserPictureFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * User picture field migration.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureFieldTest extends MigrateDrupal6TestBase {

  static $modules = array('image', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_user_picture_field');
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
