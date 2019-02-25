<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * User picture field instance migration.
 *
 * @group user
 */
class MigrateUserPictureFieldInstanceTest extends MigrateDrupal7TestBase {

  public static $modules = ['image', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations([
      'user_picture_field',
      'user_picture_field_instance',
    ]);
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
