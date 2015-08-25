<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\d6\MigrateUploadEntityDisplayTest.
 */

namespace Drupal\file\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upload entity display.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('node', 'file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('node_type', array('type' => 'article'))->save();
    entity_create('node_type', array('type' => 'story'))->save();
    entity_create('node_type', array('type' => 'page'))->save();

    $id_mappings = array(
      'd6_upload_field_instance' => array(
        array(array(1), array('node', 'page', 'upload')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->executeMigration('d6_upload_entity_display');
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 entity display migration.
   */
  public function testUploadEntityDisplay() {
    $display = entity_get_display('node', 'page', 'default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_default', $component['type']);

    $display = entity_get_display('node', 'story', 'default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_default', $component['type']);

    // Assure this doesn't exist.
    $display = entity_get_display('node', 'article', 'default');
    $component = $display->getComponent('upload');
    $this->assertTrue(is_null($component));

    $this->assertIdentical(array('node', 'page', 'default', 'upload'), entity_load('migration', 'd6_upload_entity_display')->getIdMap()->lookupDestinationID(array('page')));
  }

}
