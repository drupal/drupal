<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserRoleTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 field to Drupal 8 migration.
 */
class MigrateFieldTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'telephone', 'link', 'file', 'image', 'datetime', 'node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate fields to field.*.*.yml',
      'description'  => 'Migrate fields',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_field');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6FieldInstance.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 field to Drupal 8 migration.
   */
  public function testFields() {
    // Text field.
    $field = entity_load('field_config', 'node.field_test');
    $expected = array('max_length' => 255);
    $this->assertEqual($field->type, "text", "Field type is text.");
    $this->assertEqual($field->status(), TRUE, "Status is TRUE");
    $this->assertEqual($field->settings, $expected, "Field type text settings are correct");

    // Integer field.
    $field = entity_load('field_config', 'node.field_test_two');
    $this->assertEqual($field->type, "integer", "Field type is integer.");

    // Float field.
    $field = entity_load('field_config', 'node.field_test_three');
    $this->assertEqual($field->type, "decimal", "Field type is decimal.");

    // Link field.
    $field = entity_load('field_config', 'node.field_test_link');
    $this->assertEqual($field->type, "link", "Field type is link.");

    // File field.
    $field = entity_load('field_config', 'node.field_test_filefield');
    $this->assertEqual($field->type, "file", "Field type is file.");

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = entity_load('field_config', 'node.field_test_imagefield');
    $this->assertEqual($field->type, "image", "Field type is image.");
    $settings = $field->getSettings();
    $this->assertEqual($settings['column_groups']['alt']['label'], 'Test alt');
    $this->assertEqual($settings['column_groups']['title']['label'], 'Test title');
    $this->assertEqual($settings['target_type'], 'file');
    $this->assertEqual($settings['uri_scheme'], 'public');
    $this->assertEqual($settings['default_image']['fid'], '');
    $this->assertEqual(array_filter($settings['default_image']), array());

    // Phone field.
    $field = entity_load('field_config', 'node.field_test_phone');
    $this->assertEqual($field->type, "telephone", "Field type is telephone.");

    // Date field.
    $field = entity_load('field_config', 'node.field_test_datetime');
    $this->assertEqual($field->type, "datetime", "Field type is datetime.");
    $this->assertEqual($field->status(), FALSE, "Status is FALSE");
  }

}
