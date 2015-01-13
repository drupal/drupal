<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Migrate fields.
 *
 * @group migrate_drupal
 */
class MigrateFieldTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'telephone', 'link', 'file', 'image', 'datetime', 'node', 'options');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_field');
    $dumps = array(
      $this->getDumpDirectory() . '/ContentNodeFieldInstance.php',
      $this->getDumpDirectory() . '/ContentNodeField.php',
      $this->getDumpDirectory() . '/ContentFieldTest.php',
      $this->getDumpDirectory() . '/ContentFieldTestTwo.php',
      $this->getDumpDirectory() . '/ContentFieldMultivalue.php',
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
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::load('node.field_test');
    $expected = array('max_length' => 255);
    $this->assertEqual($field_storage->type, "text",  t('Field type is @fieldtype. It should be text.', array('@fieldtype' => $field_storage->type)));
    $this->assertEqual($field_storage->status(), TRUE, "Status is TRUE");
    $this->assertEqual($field_storage->settings, $expected, "Field type text settings are correct");

    // Integer field.
    $field_storage = FieldStorageConfig::load('node.field_test_two');
    $this->assertEqual($field_storage->type, "integer",  t('Field type is @fieldtype. It should be integer.', array('@fieldtype' => $field_storage->type)));

    // Float field.
    $field_storage = FieldStorageConfig::load('node.field_test_three');
    $this->assertEqual($field_storage->type, "decimal",  t('Field type is @fieldtype. It should be decimal.', array('@fieldtype' => $field_storage->type)));

    // Link field.
    $field_storage = FieldStorageConfig::load('node.field_test_link');
    $this->assertEqual($field_storage->type, "link",  t('Field type is @fieldtype. It should be link.', array('@fieldtype' => $field_storage->type)));

    // File field.
    $field_storage = FieldStorageConfig::load('node.field_test_filefield');
    $this->assertEqual($field_storage->type, "file",  t('Field type is @fieldtype. It should be file.', array('@fieldtype' => $field_storage->type)));

    $field_storage = FieldStorageConfig::load('node.field_test_imagefield');
    $this->assertEqual($field_storage->type, "image",  t('Field type is @fieldtype. It should be image.', array('@fieldtype' => $field_storage->type)));
    $settings = $field_storage->getSettings();
    $this->assertEqual($settings['target_type'], 'file');
    $this->assertEqual($settings['uri_scheme'], 'public');
    $this->assertEqual($settings['default_image']['uuid'], '');
    $this->assertEqual(array_filter($settings['default_image']), array());

    // Phone field.
    $field_storage = FieldStorageConfig::load('node.field_test_phone');
    $this->assertEqual($field_storage->type, "telephone",  t('Field type is @fieldtype. It should be telephone.', array('@fieldtype' => $field_storage->type)));

    // Date field.
    $field_storage = FieldStorageConfig::load('node.field_test_datetime');
    $this->assertEqual($field_storage->type, "datetime",  t('Field type is @fieldtype. It should be datetime.', array('@fieldtype' => $field_storage->type)));
    $this->assertEqual($field_storage->status(), TRUE);

    // Decimal field with radio buttons.
    $field_storage = FieldStorageConfig::load('node.field_test_decimal_radio_buttons');
    $this->assertEqual($field_storage->type, "list_float",  t('Field type is @fieldtype. It should be list_float.', array('@fieldtype' => $field_storage->type)));
    $this->assertNotNull($field_storage->settings['allowed_values']['1.2'], t('First allowed value key is set to 1.2'));
    $this->assertNotNull($field_storage->settings['allowed_values']['2.1'], t('Second allowed value key is set to 2.1'));
    $this->assertEqual($field_storage->settings['allowed_values']['1.2'], '1.2', t('First allowed value is set to 1.2'));
    $this->assertEqual($field_storage->settings['allowed_values']['2.1'], '2.1', t('Second allowed value is set to 1.2'));

    // Float field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_float_single_checkbox');
    $this->assertEqual($field_storage->type, "boolean",  t('Field type is @fieldtype. It should be boolean.', array('@fieldtype' => $field_storage->type)));

    // Integer field with a select list.
    $field_storage = FieldStorageConfig::load('node.field_test_integer_selectlist');
    $this->assertEqual($field_storage->type, "list_integer",  t('Field type is @fieldtype. It should be list_integer.', array('@fieldtype' => $field_storage->type)));
    $this->assertNotNull($field_storage->settings['allowed_values']['1234'], t('First allowed value key is set to 1234'));
    $this->assertNotNull($field_storage->settings['allowed_values']['2341'], t('Second allowed value key is set to 2341'));
    $this->assertNotNull($field_storage->settings['allowed_values']['3412'], t('Third allowed value key is set to 3412'));
    $this->assertNotNull($field_storage->settings['allowed_values']['4123'], t('Fourth allowed value key is set to 4123'));
    $this->assertEqual($field_storage->settings['allowed_values']['1234'], '1234', t('First allowed value is set to 1234'));
    $this->assertEqual($field_storage->settings['allowed_values']['2341'], '2341', t('Second allowed value is set to 2341'));
    $this->assertEqual($field_storage->settings['allowed_values']['3412'], '3412', t('Third allowed value is set to 3412'));
    $this->assertEqual($field_storage->settings['allowed_values']['4123'], '4123', t('Fourth allowed value is set to 4123'));

    // Text field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_text_single_checkbox');
    $this->assertEqual($field_storage->type, "boolean",  t('Field type is @fieldtype. It should be boolean.', array('@fieldtype' => $field_storage->type)));

  }

}
