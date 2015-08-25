<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\d6\MigrateUploadInstanceTest.
 */

namespace Drupal\file\Tests\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upload field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadInstanceTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('file', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some node mappings to get past checkRequirements().
    $id_mappings = array(
      'd6_upload_field' => array(
        array(array(1), array('node', 'upload')),
      ),
      'd6_node_type' => array(
        array(array('page'), array('page')),
        array(array('story'), array('story')),
      ),
    );
    $this->prepareMigrations($id_mappings);

    foreach (array('page', 'story') as $type) {
      entity_create('node_type', array('type' => $type))->save();
    }
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'upload',
      'type' => 'file',
      'translatable' => '0',
    ))->save();

    $this->executeMigration('d6_upload_field_instance');
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 field instance migration.
   */
  public function testUploadFieldInstance() {
    $field = FieldConfig::load('node.page.upload');
    $settings = $field->getSettings();
    $this->assertIdentical('node.page.upload', $field->id());
    $this->assertIdentical('jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp', $settings['file_extensions']);
    $this->assertIdentical('1MB', $settings['max_filesize']);
    $this->assertIdentical(TRUE, $settings['description_field']);

    $field = FieldConfig::load('node.story.upload');
    $this->assertIdentical('node.story.upload', $field->id());

    // Shouldn't exist.
    $field = FieldConfig::load('node.article.upload');
    $this->assertTrue(is_null($field));

    $this->assertIdentical(array('node', 'page', 'upload'), entity_load('migration', 'd6_upload_field_instance')->getIdMap()->lookupDestinationID(array('page')));
  }

}
