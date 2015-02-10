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
    $this->assertIdentical($field_storage->getType(), "text",  t('Field type is @fieldtype. It should be text.', array('@fieldtype' => $field_storage->getType())));
    $this->assertIdentical($field_storage->getSettings(), $expected, "Field type text settings are correct");

    // Integer field.
    $field_storage = FieldStorageConfig::load('node.field_test_two');
    $this->assertIdentical($field_storage->getType(), "integer",  t('Field type is @fieldtype. It should be integer.', array('@fieldtype' => $field_storage->getType())));

    // Float field.
    $field_storage = FieldStorageConfig::load('node.field_test_three');
    $this->assertIdentical($field_storage->getType(), "decimal",  t('Field type is @fieldtype. It should be decimal.', array('@fieldtype' => $field_storage->getType())));

    // Link field.
    $field_storage = FieldStorageConfig::load('node.field_test_link');
    $this->assertIdentical($field_storage->getType(), "link",  t('Field type is @fieldtype. It should be link.', array('@fieldtype' => $field_storage->getType())));

    // File field.
    $field_storage = FieldStorageConfig::load('node.field_test_filefield');
    $this->assertIdentical($field_storage->getType(), "file",  t('Field type is @fieldtype. It should be file.', array('@fieldtype' => $field_storage->getType())));

    $field_storage = FieldStorageConfig::load('node.field_test_imagefield');
    $this->assertIdentical($field_storage->getType(), "image",  t('Field type is @fieldtype. It should be image.', array('@fieldtype' => $field_storage->getType())));
    $settings = $field_storage->getSettings();
    $this->assertIdentical($settings['target_type'], 'file');
    $this->assertIdentical($settings['uri_scheme'], 'public');
    $this->assertIdentical(array_filter($settings['default_image']), array());

    // Phone field.
    $field_storage = FieldStorageConfig::load('node.field_test_phone');
    $this->assertIdentical($field_storage->getType(), "telephone",  t('Field type is @fieldtype. It should be telephone.', array('@fieldtype' => $field_storage->getType())));

    // Date field.
    $field_storage = FieldStorageConfig::load('node.field_test_datetime');
    $this->assertIdentical($field_storage->getType(), "datetime",  t('Field type is @fieldtype. It should be datetime.', array('@fieldtype' => $field_storage->getType())));

    // Decimal field with radio buttons.
    $field_storage = FieldStorageConfig::load('node.field_test_decimal_radio_buttons');
    $this->assertIdentical($field_storage->getType(), "list_float",  t('Field type is @fieldtype. It should be list_float.', array('@fieldtype' => $field_storage->getType())));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1.2'], t('First allowed value key is set to 1.2'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value key is set to 2.1'));
    $this->assertIdentical($field_storage->getSetting('allowed_values')['1.2'], '1.2', t('First allowed value is set to 1.2'));
    $this->assertIdentical($field_storage->getSetting('allowed_values')['2.1'], '2.1', t('Second allowed value is set to 1.2'));

    // Float field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_float_single_checkbox');
    $this->assertIdentical($field_storage->getType(), "boolean",  t('Field type is @fieldtype. It should be boolean.', array('@fieldtype' => $field_storage->getType())));

    // Integer field with a select list.
    $field_storage = FieldStorageConfig::load('node.field_test_integer_selectlist');
    $this->assertIdentical($field_storage->getType(), "list_integer",  t('Field type is @fieldtype. It should be list_integer.', array('@fieldtype' => $field_storage->getType())));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1234'], t('First allowed value key is set to 1234'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2341'], t('Second allowed value key is set to 2341'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['3412'], t('Third allowed value key is set to 3412'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['4123'], t('Fourth allowed value key is set to 4123'));
    $this->assertIdentical($field_storage->getSetting('allowed_values')['1234'], '1234', t('First allowed value is set to 1234'));
    $this->assertIdentical($field_storage->getSetting('allowed_values')['2341'], '2341', t('Second allowed value is set to 2341'));
    $this->assertIdentical($field_storage->getSetting('allowed_values')['3412'], '3412', t('Third allowed value is set to 3412'));
    $this->assertIdentical($field_storage->getSetting('allowed_values')['4123'], '4123', t('Fourth allowed value is set to 4123'));

    // Text field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_text_single_checkbox');
    $this->assertIdentical($field_storage->getType(), "boolean",  t('Field type is @fieldtype. It should be boolean.', array('@fieldtype' => $field_storage->getType())));

  }

}
