<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserPictureEntityDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 user picture to Drupal 8 entity display migration.
 */
class MigrateUserPictureEntityDisplayTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('image');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate user picture entity display.',
      'description'  => 'User picture entity display',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $id_mappings = array(
      'd6_user_picture_field_instance' => array(
        array(array(1), array('user', 'user', 'user_picture')),
      ),
    );
    $this->prepareIdMappings($id_mappings);

    $migration = entity_load('migration', 'd6_user_picture_entity_display');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 user picture to Drupal 8 entity display migration.
   */
  public function testUserPictureEntityDisplay() {
    $display = entity_get_display('user', 'user', 'default');
    $component = $display->getComponent('user_picture');
    $this->assertEqual($component['type'], 'image');
    $this->assertEqual($component['settings']['image_link'], 'content');

    $this->assertEqual(array('user', 'user', 'default', 'user_picture'), entity_load('migration', 'd6_user_picture_entity_display')->getIdMap()->lookupDestinationID(array('')));
  }

}
