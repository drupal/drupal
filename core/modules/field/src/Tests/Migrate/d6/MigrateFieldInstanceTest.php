<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Migrate\d6\MigrateFieldInstanceTest.
 */

namespace Drupal\field\Tests\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate field instances.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'telephone',
    'link',
    'file',
    'image',
    'datetime',
    'node',
    'field',
    'text',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_field' => array(
        array(array('field_test'), array('node', 'field_test')),
        array(array('field_test_two'), array('node', 'field_test_two')),
        array(array('field_test_three'), array('node', 'field_test_three')),
        array(array('field_test_four'), array('node', 'field_test_four')),
        array(array('field_test_email'), array('node', 'field_test_email')),
        array(array('field_test_link'), array('node', 'field_test_link')),
        array(array('field_test_filefield'), array('node', 'field_test_filefield')),
        array(array('field_test_imagefield'), array('node', 'field_test_imagefield')),
        array(array('field_test_phone'), array('node', 'field_test_phone')),
        array(array('field_test_date'), array('node', 'field_test_date')),
        array(array('field_test_datestamp'), array('node', 'field_test_datestamp')),
        array(array('field_test_datetime'), array('node', 'field_test_datetime')),
      ),
      'd6_node_type' => array(
        array(array('page'), array('page')),
        array(array('story'), array('story')),
        array(array('test_page'), array('test_page')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    entity_create('node_type', array('type' => 'page'))->save();
    entity_create('node_type', array('type' => 'story'))->save();
    entity_create('node_type', array('type' => 'test_page'))->save();

    $this->createFields();
    $this->executeMigration('d6_field_instance');
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFieldInstanceSettings() {
    $entity = entity_create('node', array('type' => 'story'));
    // Test a text field.
    $field = FieldConfig::load('node.story.field_test');
    $this->assertIdentical('Text Field', $field->label());
    $expected = array('max_length' => 255);
    $this->assertIdentical($expected, $field->getSettings());
    $this->assertIdentical('text for default value', $entity->field_test->value);

    // Test a number field.
    $field = FieldConfig::load('node.story.field_test_two');
    $this->assertIdentical('Integer Field', $field->label());
    $expected = array(
      'min' => 10,
      'max' => 100,
      'prefix' => 'pref',
      'suffix' => 'suf',
      'unsigned' => FALSE,
      'size' => 'normal',
    );
    $this->assertIdentical($expected, $field->getSettings());

    $field = FieldConfig::load('node.story.field_test_four');
    $this->assertIdentical('Float Field', $field->label());
    $expected = array(
      'min' => 100.0,
      'max' => 200.0,
      'prefix' => 'id-',
      'suffix' => '',
    );
    $this->assertIdentical($expected, $field->getSettings());

    // Test email field.
    $field = FieldConfig::load('node.story.field_test_email');
    $this->assertIdentical('Email Field', $field->label());
    $this->assertIdentical('benjy@example.com', $entity->field_test_email->value);

    // Test a filefield.
    $field = FieldConfig::load('node.story.field_test_filefield');
    $this->assertIdentical('File Field', $field->label());
    $expected = array(
      'file_extensions' => 'txt pdf doc',
      'file_directory' => 'images',
      'description_field' => TRUE,
      'max_filesize' => '200KB',
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => 'public',
      // This value should be 'default:file' but the test does not migrate field
      // storages so we end up with the default value for this setting.
      'handler' => 'default:node',
      'handler_settings' => array(),
      'target_bundle' => NULL,
    );
    $field_settings = $field->getSettings();
    ksort($expected);
    ksort($field_settings);
    // This is the only way to compare arrays.
    $this->assertIdentical($expected, $field_settings);

    // Test a link field.
    $field = FieldConfig::load('node.story.field_test_link');
    $this->assertIdentical('Link Field', $field->label());
    $expected = array('title' => 2, 'link_type' => LinkItemInterface::LINK_GENERIC);
    $this->assertIdentical($expected, $field->getSettings());
    $this->assertIdentical('default link title', $entity->field_test_link->title, 'Field field_test_link default title is correct.');
    $this->assertIdentical('https://www.drupal.org', $entity->field_test_link->url, 'Field field_test_link default title is correct.');
    $this->assertIdentical([], $entity->field_test_link->options['attributes']);
  }

  /**
   * Helper to create fields.
   */
  protected function createFields() {
    $fields = array(
      'field_test' => 'text',
      'field_test_two' => 'integer',
      'field_test_three' => 'decimal',
      'field_test_four' => 'float',
      'field_test_email' => 'email',
      'field_test_link' => 'link',
      'field_test_filefield' => 'file',
      'field_test_imagefield' => 'image',
      'field_test_phone' => 'telephone',
      'field_test_date' => 'datetime',
      'field_test_datestamp' => 'datetime',
      'field_test_datetime' => 'datetime',
    );
    foreach ($fields as $name => $type) {
      entity_create('field_storage_config', array(
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
      ))->save();
    }

  }

}
