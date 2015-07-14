<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserPictureInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldConfig;

/**
 * User picture field instance migration.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureInstanceTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('image', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some node mappings to get past checkRequirements().
    $id_mappings = array(
      'd6_user_picture_field' => array(
        array(array('user_upload'), array('name', 'bundle')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'type' => 'image',
      'translatable' => '0',
    ))->save();

    $this->executeMigration('d6_user_picture_field_instance');
  }

  /**
   * Tests the Drupal 6 user picture to Drupal 8 picture field instance migration.
   */
  public function testUserPictureFieldInstance() {
    $field = FieldConfig::load('user.user.user_picture');
    $settings = $field->getSettings();
    $this->assertIdentical('png gif jpg jpeg', $settings['file_extensions']);
    $this->assertIdentical('pictures', $settings['file_directory']);
    $this->assertIdentical('30KB', $settings['max_filesize']);
    $this->assertIdentical('85x85', $settings['max_resolution']);

    $this->assertIdentical(array('user', 'user', 'user_picture'), entity_load('migration', 'd6_user_picture_field_instance')->getIdMap()->lookupDestinationID(array('')));
  }

}
