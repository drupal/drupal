<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserPictureEntityDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * User picture entity display.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureEntityDisplayTest extends MigrateDrupal6TestBase {

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
    $this->executeMigration('d6_user_picture_entity_display');
  }

  /**
   * Tests the Drupal 6 user picture to Drupal 8 entity display migration.
   */
  public function testUserPictureEntityDisplay() {
    $display = entity_get_display('user', 'user', 'default');
    $component = $display->getComponent('user_picture');
    $this->assertIdentical('image', $component['type']);
    $this->assertIdentical('content', $component['settings']['image_link']);

    $this->assertIdentical(array('user', 'user', 'default', 'user_picture'), entity_load('migration', 'd6_user_picture_entity_display')->getIdMap()->lookupDestinationID(array('')));
  }

}
