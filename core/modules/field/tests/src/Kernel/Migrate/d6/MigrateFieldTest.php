<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate fields.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_field');
  }

  /**
   * Tests the Drupal 6 field to Drupal 8 migration.
   */
  public function testFields() {
    // Text field.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::load('node.field_test');
    $this->assertIdentical('text_long', $field_storage->getType());
    // text_long fields do not have settings.
    $this->assertIdentical([], $field_storage->getSettings());

    // Integer field.
    $field_storage = FieldStorageConfig::load('node.field_test_two');
    $this->assertIdentical("integer", $field_storage->getType(), t('Field type is @fieldtype. It should be integer.', ['@fieldtype' => $field_storage->getType()]));

    // Float field.
    $field_storage = FieldStorageConfig::load('node.field_test_three');
    $this->assertIdentical("decimal", $field_storage->getType(), t('Field type is @fieldtype. It should be decimal.', ['@fieldtype' => $field_storage->getType()]));

    // Link field.
    $field_storage = FieldStorageConfig::load('node.field_test_link');
    $this->assertIdentical("link", $field_storage->getType(), t('Field type is @fieldtype. It should be link.', ['@fieldtype' => $field_storage->getType()]));

    // File field.
    $field_storage = FieldStorageConfig::load('node.field_test_filefield');
    $this->assertIdentical("file", $field_storage->getType(), t('Field type is @fieldtype. It should be file.', ['@fieldtype' => $field_storage->getType()]));

    $field_storage = FieldStorageConfig::load('node.field_test_imagefield');
    $this->assertIdentical("image", $field_storage->getType(), t('Field type is @fieldtype. It should be image.', ['@fieldtype' => $field_storage->getType()]));
    $settings = $field_storage->getSettings();
    $this->assertIdentical('file', $settings['target_type']);
    $this->assertIdentical('public', $settings['uri_scheme']);
    $this->assertIdentical([], array_filter($settings['default_image']));

    // Phone field.
    $field_storage = FieldStorageConfig::load('node.field_test_phone');
    $this->assertIdentical("telephone", $field_storage->getType(), t('Field type is @fieldtype. It should be telephone.', ['@fieldtype' => $field_storage->getType()]));

    // Date field.
    $field_storage = FieldStorageConfig::load('node.field_test_datetime');
    $this->assertIdentical("datetime", $field_storage->getType(), t('Field type is @fieldtype. It should be datetime.', ['@fieldtype' => $field_storage->getType()]));

    // Decimal field with radio buttons.
    $field_storage = FieldStorageConfig::load('node.field_test_decimal_radio_buttons');
    $this->assertIdentical("list_float", $field_storage->getType(), t('Field type is @fieldtype. It should be list_float.', ['@fieldtype' => $field_storage->getType()]));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1.2'], t('First allowed value key is set to 1.2'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value key is set to 2.1'));
    $this->assertIdentical('1.2', $field_storage->getSetting('allowed_values')['1.2'], t('First allowed value is set to 1.2'));
    $this->assertIdentical('2.1', $field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value is set to 1.2'));

    // Float field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_float_single_checkbox');
    $this->assertIdentical("boolean", $field_storage->getType(), t('Field type is @fieldtype. It should be boolean.', ['@fieldtype' => $field_storage->getType()]));

    // Integer field with a select list.
    $field_storage = FieldStorageConfig::load('node.field_test_integer_selectlist');
    $this->assertIdentical("list_integer", $field_storage->getType(), t('Field type is @fieldtype. It should be list_integer.', ['@fieldtype' => $field_storage->getType()]));
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
    $this->assertIdentical("boolean", $field_storage->getType(), t('Field type is @fieldtype. It should be boolean.', ['@fieldtype' => $field_storage->getType()]));

    // Validate that the source count and processed count match up.
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('d6_field');
    $this->assertIdentical($migration->getSourcePlugin()->count(), $migration->getIdMap()->processedCount());

    // Check that we've reported on a conflict in widget_types.
    $messages = [];
    foreach ($migration->getIdMap()->getMessageIterator() as $message_row) {
      $messages[] = $message_row->message;
    }
    $this->assertIdentical(count($messages), 1);
    $this->assertIdentical($messages[0], 'Widget types optionwidgets_onoff, text_textfield are used in Drupal 6 field instances: widget type optionwidgets_onoff applied to the Drupal 8 base field');
  }

}
