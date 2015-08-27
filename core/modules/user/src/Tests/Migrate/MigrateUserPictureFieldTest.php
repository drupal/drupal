<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserPictureFieldTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * User picture field migration.
 *
 * @group user
 */
class MigrateUserPictureFieldTest extends MigrateDrupal7TestBase {

  static $modules = array('image', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('user_picture_field');
  }

  /**
   * Test the user picture field migration.
   */
  public function testUserPictureField() {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = FieldStorageConfig::load('user.user_picture');
    $this->assertTrue($field_storage instanceof FieldStorageConfigInterface);
    $this->assertIdentical('user.user_picture', $field_storage->id());
    $this->assertIdentical('image', $field_storage->getType());
    $this->assertIdentical('user', $field_storage->getTargetEntityTypeId());
  }

}
