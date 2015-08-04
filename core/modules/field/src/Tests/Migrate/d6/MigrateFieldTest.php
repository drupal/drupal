<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Migrate\d6\MigrateFieldTest.
 */

namespace Drupal\field\Tests\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate fields.
 *
 * @group field
 */
class MigrateFieldTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'telephone', 'link', 'file', 'image', 'datetime', 'node', 'options', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps([
      'ContentNodeFieldInstance.php',
      'ContentNodeField.php',
      'ContentFieldTest.php',
      'ContentFieldTestTwo.php',
      'ContentFieldMultivalue.php',
    ]);
    $this->executeMigration('d6_field');
  }

  /**
   * Tests the Drupal 6 field to Drupal 8 migration.
   */
  public function testFields() {
    // Text field.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::load('node.field_test');
    $expected = array('max_length' => 255);
    $this->assertIdentical("text", $field_storage->getType(),  t('Field type is @fieldtype. It should be text.', array('@fieldtype' => $field_storage->getType())));
    $this->assertIdentical($expected, $field_storage->getSettings(), "Field type text settings are correct");

    // Integer field.
    $field_storage = FieldStorageConfig::load('node.field_test_two');
    $this->assertIdentical("integer", $field_storage->getType(),  t('Field type is @fieldtype. It should be integer.', array('@fieldtype' => $field_storage->getType())));

    // Float field.
    $field_storage = FieldStorageConfig::load('node.field_test_three');
    $this->assertIdentical("decimal", $field_storage->getType(),  t('Field type is @fieldtype. It should be decimal.', array('@fieldtype' => $field_storage->getType())));

    // Link field.
    $field_storage = FieldStorageConfig::load('node.field_test_link');
    $this->assertIdentical("link", $field_storage->getType(),  t('Field type is @fieldtype. It should be link.', array('@fieldtype' => $field_storage->getType())));

    // File field.
    $field_storage = FieldStorageConfig::load('node.field_test_filefield');
    $this->assertIdentical("file", $field_storage->getType(),  t('Field type is @fieldtype. It should be file.', array('@fieldtype' => $field_storage->getType())));

    $field_storage = FieldStorageConfig::load('node.field_test_imagefield');
    $this->assertIdentical("image", $field_storage->getType(),  t('Field type is @fieldtype. It should be image.', array('@fieldtype' => $field_storage->getType())));
    $settings = $field_storage->getSettings();
    $this->assertIdentical('file', $settings['target_type']);
    $this->assertIdentical('public', $settings['uri_scheme']);
    $this->assertIdentical(array(), array_filter($settings['default_image']));

    // Phone field.
    $field_storage = FieldStorageConfig::load('node.field_test_phone');
    $this->assertIdentical("telephone", $field_storage->getType(),  t('Field type is @fieldtype. It should be telephone.', array('@fieldtype' => $field_storage->getType())));

    // Date field.
    $field_storage = FieldStorageConfig::load('node.field_test_datetime');
    $this->assertIdentical("datetime", $field_storage->getType(),  t('Field type is @fieldtype. It should be datetime.', array('@fieldtype' => $field_storage->getType())));

    // Decimal field with radio buttons.
    $field_storage = FieldStorageConfig::load('node.field_test_decimal_radio_buttons');
    $this->assertIdentical("list_float", $field_storage->getType(),  t('Field type is @fieldtype. It should be list_float.', array('@fieldtype' => $field_storage->getType())));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1.2'], t('First allowed value key is set to 1.2'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value key is set to 2.1'));
    $this->assertIdentical('1.2', $field_storage->getSetting('allowed_values')['1.2'], t('First allowed value is set to 1.2'));
    $this->assertIdentical('2.1', $field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value is set to 1.2'));

    // Float field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_float_single_checkbox');
    $this->assertIdentical("boolean", $field_storage->getType(),  t('Field type is @fieldtype. It should be boolean.', array('@fieldtype' => $field_storage->getType())));

    // Integer field with a select list.
    $field_storage = FieldStorageConfig::load('node.field_test_integer_selectlist');
    $this->assertIdentical("list_integer", $field_storage->getType(),  t('Field type is @fieldtype. It should be list_integer.', array('@fieldtype' => $field_storage->getType())));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1234'], t('First allowed value key is set to 1234'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2341'], t('Second allowed value key is set to 2341'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['3412'], t('Third allowed value key is set to 3412'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['4123'], t('Fourth allowed value key is set to 4123'));
    $this->assertIdentical('1234', $field_storage->getSetting('allowed_values')['1234'], t('First allowed value is set to 1234'));
    $this->assertIdentical('2341', $field_storage->getSetting('allowed_values')['2341'], t('Second allowed value is set to 2341'));
    $this->assertIdentical('3412', $field_storage->getSetting('allowed_values')['3412'], t('Third allowed value is set to 3412'));
    $this->assertIdentical('4123', $field_storage->getSetting('allowed_values')['4123'], t('Fourth allowed value is set to 4123'));

    // Text field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_text_single_checkbox');
    $this->assertIdentical("boolean", $field_storage->getType(),  t('Field type is @fieldtype. It should be boolean.', array('@fieldtype' => $field_storage->getType())));

  }

}
