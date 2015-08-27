<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserPictureEntityFormDisplayTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of the user_picture field's entity form display settings.
 *
 * @group user
 */
class MigrateUserPictureEntityFormDisplayTest extends MigrateDrupal7TestBase {

  static $modules = array('image', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('user_picture_field');
    $this->executeMigration('user_picture_field_instance');
    $this->executeMigration('user_picture_entity_form_display');
  }

  /**
   * Tests the field's entity form display settings.
   */
  public function testEntityFormDisplaySettings() {
    $component = entity_get_form_display('user', 'user', 'default')->getComponent('user_picture');
    $this->assertIdentical('image_image', $component['type']);
    $this->assertIdentical('throbber', $component['settings']['progress_indicator']);
    $this->assertIdentical('thumbnail', $component['settings']['preview_image_style']);
  }

}
