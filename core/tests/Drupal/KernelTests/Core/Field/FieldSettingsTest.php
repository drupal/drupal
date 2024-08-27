<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests field settings methods on field definition structures.
 *
 * @group Field
 */
class FieldSettingsTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'field_test'];

  /**
   * @covers \Drupal\Core\Field\BaseFieldDefinition::getSettings
   * @covers \Drupal\Core\Field\BaseFieldDefinition::setSettings
   */
  public function testBaseFieldSettings(): void {
    $base_field = BaseFieldDefinition::create('test_field');

    // Check that the default settings have been populated.
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
      'test_field_setting' => 'dummy test string',
      'translatable_field_setting' => 'a translatable field setting',
    ];
    $this->assertEquals($expected_settings, $base_field->getSettings());

    // Change one single setting using setSettings(), and check that the other
    // expected settings are still present.
    $expected_settings['test_field_setting'] = 'another test string';
    $base_field->setSettings(['test_field_setting' => $expected_settings['test_field_setting']]);
    $this->assertEquals($expected_settings, $base_field->getSettings());
  }

  /**
   * Tests the base field settings on a cloned base field definition object.
   */
  public function testBaseFieldSettingsOnClone(): void {
    $base_field = BaseFieldDefinition::create('test_field');

    // Check that the default settings have been populated.
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
      'test_field_setting' => 'dummy test string',
      'translatable_field_setting' => 'a translatable field setting',
    ];
    $this->assertEquals($expected_settings, $base_field->getSettings());

    // Clone the base field object and change one single setting using
    // setSettings() on the cloned base field and check that it has been
    // changed only on the cloned object.
    $clone_base_field = clone $base_field;
    $expected_settings_clone = $expected_settings;
    $expected_settings_clone['changeable'] = $expected_settings['changeable'] . ' (clone)';
    $clone_base_field->setSetting('changeable', $expected_settings_clone['changeable']);
    $this->assertEquals($expected_settings, $base_field->getSettings());
    $this->assertEquals($expected_settings_clone, $clone_base_field->getSettings());
  }

  /**
   * @covers \Drupal\field\Entity\FieldStorageConfig::getSettings
   * @covers \Drupal\field\Entity\FieldStorageConfig::setSettings
   */
  public function testConfigurableFieldStorageSettings(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $field_storage->save();
    // Check that the default settings have been populated.
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
      'storage_setting_from_config_data' => 'TRUE',
    ];
    $this->assertEquals($expected_settings, $field_storage->getSettings());

    // Change one single setting using setSettings(), and check that the other
    // expected settings are still present.
    $expected_settings['test_field_storage_setting'] = 'another test string';
    $field_storage->setSettings(['test_field_storage_setting' => $expected_settings['test_field_storage_setting']]);
    $this->assertEquals($expected_settings, $field_storage->getSettings());
  }

  /**
   * @covers \Drupal\field\Entity\FieldStorageConfig::getSettings
   * @covers \Drupal\field\Entity\FieldStorageConfig::setSettings
   */
  public function testConfigurableFieldSettings(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $field_storage->save();
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
      'storage_setting_from_config_data' => 'TRUE',
    ];
    $this->assertEquals($expected_settings, $field_storage->getSettings());

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);

    // Check that the default settings have been populated. Note: getSettings()
    // returns both storage and field settings.
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
      'test_field_setting' => 'dummy test string',
      'translatable_field_setting' => 'a translatable field setting',
      'storage_setting_from_config_data' => 'TRUE',
    ];
    $this->assertEquals($expected_settings, $field->getSettings());

    // Change one single setting using setSettings(), and check that the other
    // expected settings are still present.
    $expected_settings['test_field_setting'] = 'another test string';
    $field->setSettings(['test_field_setting' => $expected_settings['test_field_setting']]);
    $this->assertEquals($expected_settings, $field->getSettings());

    // Save the field and check the expected settings.
    $field->save();

    $expected_settings['field_setting_from_config_data'] = 'TRUE';
    $this->assertEquals($expected_settings, $field->getSettings());

    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'test_field');

    $this->assertEquals($expected_settings, $field->getSettings());

    $expected_settings['test_field_setting'] = 'yet another test string';
    $field->setSettings(['test_field_setting' => $expected_settings['test_field_setting']]);
    $this->assertEquals($expected_settings, $field->getSettings());
  }

}
