<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\MigrateUserPictureEntityDisplayTest.
 */

namespace Drupal\user\Tests\Migrate;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * User picture entity display.
 *
 * @group user
 */
class MigrateUserPictureEntityDisplayTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('file', 'image');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->executeMigration('user_picture_field');
    $this->executeMigration('user_picture_field_instance');
    $this->executeMigration('user_picture_entity_display');
  }

  /**
   * Tests the Drupal 7 user picture to Drupal 8 entity display migration.
   */
  public function testUserPictureEntityDisplay() {
    $component = entity_get_display('user', 'user', 'default')->getComponent('user_picture');
    $this->assertIdentical('image', $component['type']);
    $this->assertIdentical('', $component['settings']['image_style']);
    $this->assertIdentical('content', $component['settings']['image_link']);
  }

}
