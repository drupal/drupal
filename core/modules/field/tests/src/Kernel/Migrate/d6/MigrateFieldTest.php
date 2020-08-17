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
  protected function setUp(): void {
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
    $this->assertSame('text_long', $field_storage->getType());
    // text_long fields do not have settings.
    $this->assertSame([], $field_storage->getSettings());

    // Integer field.
    $field_storage = FieldStorageConfig::load('node.field_test_two');
    $this->assertSame("integer", $field_storage->getType(), t('Field type is @fieldtype. It should be integer.', ['@fieldtype' => $field_storage->getType()]));

    // Float field.
    $field_storage = FieldStorageConfig::load('node.field_test_three');
    $this->assertSame("decimal", $field_storage->getType(), t('Field type is @fieldtype. It should be decimal.', ['@fieldtype' => $field_storage->getType()]));

    // Link field.
    $field_storage = FieldStorageConfig::load('node.field_test_link');
    $this->assertSame("link", $field_storage->getType(), t('Field type is @fieldtype. It should be link.', ['@fieldtype' => $field_storage->getType()]));

    // File field.
    $field_storage = FieldStorageConfig::load('node.field_test_filefield');
    $this->assertSame("file", $field_storage->getType(), t('Field type is @fieldtype. It should be file.', ['@fieldtype' => $field_storage->getType()]));

    $field_storage = FieldStorageConfig::load('node.field_test_imagefield');
    $this->assertSame("image", $field_storage->getType(), t('Field type is @fieldtype. It should be image.', ['@fieldtype' => $field_storage->getType()]));
    $settings = $field_storage->getSettings();
    $this->assertSame('file', $settings['target_type']);
    $this->assertSame('public', $settings['uri_scheme']);
    $this->assertSame([], array_filter($settings['default_image']));

    // Phone field.
    $field_storage = FieldStorageConfig::load('node.field_test_phone');
    $this->assertSame("telephone", $field_storage->getType(), t('Field type is @fieldtype. It should be telephone.', ['@fieldtype' => $field_storage->getType()]));

    // Date field.
    $field_storage = FieldStorageConfig::load('node.field_test_datetime');
    $this->assertSame("datetime", $field_storage->getType(), t('Field type is @fieldtype. It should be datetime.', ['@fieldtype' => $field_storage->getType()]));

    // Date fields.
    $field_storage = FieldStorageConfig::load('node.field_test_datetime');
    $this->assertSame("datetime", $field_storage->getType(), t('Field type is @fieldtype. It should be datetime.', ['@fieldtype' => $field_storage->getType()]));
    $field_storage = FieldStorageConfig::load('node.field_test_datestamp');
    $this->assertSame("timestamp", $field_storage->getType(), t('Field type is @fieldtype. It should be timestamp.', ['@fieldtype' => $field_storage->getType()]));
    $field_storage = FieldStorageConfig::load('node.field_test_date');
    $this->assertSame("datetime", $field_storage->getType(), t('Field type is @fieldtype. It should be datetime.', ['@fieldtype' => $field_storage->getType()]));

    // Decimal field with radio buttons.
    $field_storage = FieldStorageConfig::load('node.field_test_decimal_radio_buttons');
    $this->assertSame("list_float", $field_storage->getType(), t('Field type is @fieldtype. It should be list_float.', ['@fieldtype' => $field_storage->getType()]));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1.2'], t('First allowed value key is set to 1.2'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value key is set to 2.1'));
    $this->assertSame('1.2', $field_storage->getSetting('allowed_values')['1.2'], t('First allowed value is set to 1.2'));
    $this->assertSame('2.1', $field_storage->getSetting('allowed_values')['2.1'], t('Second allowed value is set to 1.2'));

    // Email field.
    $field_storage = FieldStorageConfig::load('node.field_test_email');
    $this->assertSame("email", $field_storage->getType(), t('Field type is @fieldtype. It should be email.', ['@fieldtype' => $field_storage->getType()]));

    // Float field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_float_single_checkbox');
    $this->assertSame("boolean", $field_storage->getType(), t('Field type is @fieldtype. It should be boolean.', ['@fieldtype' => $field_storage->getType()]));

    // Integer field with a select list.
    $field_storage = FieldStorageConfig::load('node.field_test_integer_selectlist');
    $this->assertSame("list_integer", $field_storage->getType(), t('Field type is @fieldtype. It should be list_integer.', ['@fieldtype' => $field_storage->getType()]));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['1234'], t('First allowed value key is set to 1234'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['2341'], t('Second allowed value key is set to 2341'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['3412'], t('Third allowed value key is set to 3412'));
    $this->assertNotNull($field_storage->getSetting('allowed_values')['4123'], t('Fourth allowed value key is set to 4123'));
    $this->assertSame('1234', $field_storage->getSetting('allowed_values')['1234'], t('First allowed value is set to 1234'));
    $this->assertSame('2341', $field_storage->getSetting('allowed_values')['2341'], t('Second allowed value is set to 2341'));
    $this->assertSame('3412', $field_storage->getSetting('allowed_values')['3412'], t('Third allowed value is set to 3412'));
    $this->assertSame('4123', $field_storage->getSetting('allowed_values')['4123'], t('Fourth allowed value is set to 4123'));

    // Text field with a single checkbox.
    $field_storage = FieldStorageConfig::load('node.field_test_text_single_checkbox');
    $this->assertSame("boolean", $field_storage->getType(), t('Field type is @fieldtype. It should be boolean.', ['@fieldtype' => $field_storage->getType()]));

    // Test a node reference field.
    $field_storage = FieldStorageConfig::load('node.field_company');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $this->assertSame('entity_reference', $field_storage->getType());
    $this->assertSame('node', $field_storage->getSetting('target_type'));

    // Test a user reference field.
    $field_storage = FieldStorageConfig::load('node.field_commander');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $this->assertSame('entity_reference', $field_storage->getType());
    $this->assertSame('user', $field_storage->getSetting('target_type'));

    // Node reference to entity reference migration.
    $field_storage = FieldStorageConfig::load('node.field_node_reference');
    $this->assertSame('entity_reference', $field_storage->getType());
    $this->assertSame('node', $field_storage->getSetting('target_type'));

    // User reference to entity reference migration.
    $field_storage = FieldStorageConfig::load('node.field_user_reference');
    $this->assertSame('entity_reference', $field_storage->getType());
    $this->assertSame('user', $field_storage->getSetting('target_type'));

    // Validate that the source count and processed count match up.
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->getMigration('d6_field');
    $this->assertSame($migration->getSourcePlugin()->count(), $migration->getIdMap()->processedCount());

    // Check that we've reported on a conflict in widget_types.
    $messages = iterator_to_array($migration->getIdMap()->getMessages());
    $this->assertCount(1, $messages);
    $this->assertSame($messages[0]->message, 'Widget types optionwidgets_onoff, text_textfield are used in Drupal 6 field instances: widget type optionwidgets_onoff applied to the Drupal 8 base field');
  }

}
