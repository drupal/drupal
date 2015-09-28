<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserPictureEntityFormDisplayTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of the user_picture field's entity form display settings.
 *
 * @group user
 */
class MigrateUserPictureEntityFormDisplayTest extends MigrateDrupal7TestBase {

  public static $modules = ['image', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations([
      'user_picture_field',
      'user_picture_field_instance',
      'user_picture_entity_form_display',
    ]);
  }

  /**
   * Tests the field's entity form display settings.
   */
  public function testEntityFormDisplaySettings() {
    $component = EntityFormDisplay::load('user.user.default')->getComponent('user_picture');
    $this->assertIdentical('image_image', $component['type']);
    $this->assertIdentical('throbber', $component['settings']['progress_indicator']);
    $this->assertIdentical('thumbnail', $component['settings']['preview_image_style']);
  }

}
