<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d6\MigrateUserPictureEntityFormDisplayTest.
 */

namespace Drupal\user\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * User picture entity form display.
 *
 * @group migrate_drupal_6
 */
class MigrateUserPictureEntityFormDisplayTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('image');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $id_mappings = array(
      'd6_user_picture_field_instance' => array(
        array(array(1), array('user', 'user', 'user_picture')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->executeMigration('d6_user_picture_entity_form_display');
  }

  /**
   * Tests the Drupal 6 user picture to Drupal 8 entity form display migration.
   */
  public function testUserPictureEntityFormDisplay() {
    $display = entity_get_form_display('user', 'user', 'default');
    $component = $display->getComponent('user_picture');
    $this->assertIdentical('image_image', $component['type']);
    $this->assertIdentical('throbber', $component['settings']['progress_indicator']);

    $this->assertIdentical(array('user', 'user', 'default', 'user_picture'), entity_load('migration', 'd6_user_picture_entity_form_display')->getIdMap()->lookupDestinationID(array('')));
  }

}
