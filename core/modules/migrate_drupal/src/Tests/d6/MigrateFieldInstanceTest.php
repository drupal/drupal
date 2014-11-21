<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFieldInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\link\LinkItemInterface;

/**
 * Migrate field instances.
 *
 * @group migrate_drupal
 */
class MigrateFieldInstanceTest extends MigrateDrupalTestBase {

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

    $migration = entity_load('migration', 'd6_field_instance');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6FieldInstance.php',
    );
    $this->createFields();

    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFieldInstanceSettings() {
    $entity = entity_create('node', array('type' => 'story'));
    // Test a text field.
    $field = FieldConfig::load('node.story.field_test');
    $this->assertEqual($field->label(), 'Text Field');
    $expected = array('max_length' => 255);
    $this->assertEqual($field->getSettings(), $expected);
    $this->assertEqual('text for default value', $entity->field_test->value);

    // Test a number field.
    $field = FieldConfig::load('node.story.field_test_two');
    $this->assertEqual($field->label(), 'Integer Field');
    $expected = array(
      'min' => '10',
      'max' => '100',
      'prefix' => 'pref',
      'suffix' => 'suf',
      'unsigned' => '',
      'size' => 'normal',
    );
    $this->assertEqual($field->getSettings(), $expected);

    $field = FieldConfig::load('node.story.field_test_four');
    $this->assertEqual($field->label(), 'Float Field');
    $expected = array(
      'min' => 100,
      'max' => 200,
      'prefix' => 'id-',
      'suffix' => '',
    );
    $this->assertEqual($field->getSettings(), $expected);

    // Test email field.
    $field = FieldConfig::load('node.story.field_test_email');
    $this->assertEqual($field->label(), 'Email Field');
    $this->assertEqual('benjy@example.com', $entity->field_test_email->value, 'Field field_test_email default_value is correct.');

    // Test a filefield.
    $field = FieldConfig::load('node.story.field_test_filefield');
    $this->assertEqual($field->label(), 'File Field');
    $expected = array(
      'file_extensions' => 'txt pdf doc',
      'file_directory' => 'images',
      'description_field' => TRUE,
      'max_filesize' => '200KB',
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => 'public',
      'handler' => 'default',
      'target_bundle' => NULL,
    );
    // This is the only way to compare arrays.
    $this->assertFalse(array_diff_assoc($field->getSettings(), $expected));
    $this->assertFalse(array_diff_assoc($expected, $field->getSettings()));

    // Test a link field.
    $field = FieldConfig::load('node.story.field_test_link');
    $this->assertEqual($field->label(), 'Link Field');
    $expected = array('title' => 2, 'link_type' => LinkItemInterface::LINK_GENERIC);
    $this->assertEqual($field->getSettings(), $expected);
    $this->assertEqual('default link title', $entity->field_test_link->title, 'Field field_test_link default title is correct.');
    $this->assertEqual('http://drupal.org', $entity->field_test_link->url, 'Field field_test_link default title is correct.');

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
