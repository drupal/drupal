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

  protected static $modules = ['image', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'user_picture_field',
      'user_picture_field_instance',
    ]);
  }

  /**
   * Tests the user picture field migration.
   */
  public function testUserPictureField() {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('user.user.user_picture');
    $this->assertInstanceOf(FieldConfigInterface::class, $field);
    $this->assertSame('user', $field->getTargetEntityTypeId());
    $this->assertSame('user', $field->getTargetBundle());
    $this->assertSame('user_picture', $field->getName());
  }

}
