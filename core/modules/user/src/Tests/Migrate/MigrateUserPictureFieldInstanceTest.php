<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserPictureFieldInstanceTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * User picture field instance migration.
 *
 * @group user
 */
class MigrateUserPictureFieldInstanceTest extends MigrateDrupal7TestBase {

  static $modules = array('image', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('user_picture_field');
    $this->executeMigration('user_picture_field_instance');
  }

  /**
   * Test the user picture field migration.
   */
  public function testUserPictureField() {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('user.user.user_picture');
    $this->assertTrue($field instanceof FieldConfigInterface);
    $this->assertIdentical('user', $field->getTargetEntityTypeId());
    $this->assertIdentical('user', $field->getTargetBundle());
    $this->assertIdentical('user_picture', $field->getName());
  }

}
