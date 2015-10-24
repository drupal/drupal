<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Field\FieldSettingsTest.
 */

namespace Drupal\system\Tests\Field;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests field settings methods on field definition structures.
 *
 * @group Field
 */
class FieldSettingsTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'field_test');

  /**
   * @covers \Drupal\Core\Field\BaseFieldDefinition::getSettings()
   * @covers \Drupal\Core\Field\BaseFieldDefinition::setSettings()
   */
  public function testBaseFieldSettings() {
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
    $this->assertEqual($base_field->getSettings(), $expected_settings);

    // Change one single setting using setSettings(), and check that the other
    // expected settings are still present.
    $expected_settings['test_field_setting'] = 'another test string';
    $base_field->setSettings(['test_field_setting' => $expected_settings['test_field_setting']]);
    $this->assertEqual($base_field->getSettings(), $expected_settings);
  }

  /**
   * @covers \Drupal\field\Entity\FieldStorageConfig::getSettings()
   * @covers \Drupal\field\Entity\FieldStorageConfig::setSettings()
   */
  public function testConfigurableFieldStorageSettings() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ]);

    // Check that the default settings have been populated.
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
    ];
    $this->assertEqual($field_storage->getSettings(), $expected_settings);

    // Change one single setting using setSettings(), and check that the other
    // expected settings are still present.
    $expected_settings['test_field_storage_setting'] = 'another test string';
    $field_storage->setSettings(['test_field_storage_setting' => $expected_settings['test_field_storage_setting']]);
    $this->assertEqual($field_storage->getSettings(), $expected_settings);
  }

  /**
   * @covers \Drupal\field\Entity\FieldStorageConfig::getSettings()
   * @covers \Drupal\field\Entity\FieldStorageConfig::setSettings()
   */
  public function testConfigurableFieldSettings() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field'
    ]);
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test'
    ]);
    // Note: FieldConfig does not populate default settings until the config
    // is saved.
    // @todo Remove once https://www.drupal.org/node/2327883 is fixed.
    $field->save();

    // Check that the default settings have been populated. Note: getSettings()
    // returns both storage and field settings.
    $expected_settings = [
      'test_field_storage_setting' => 'dummy test string',
      'changeable' => 'a changeable field storage setting',
      'unchangeable' => 'an unchangeable field storage setting',
      'translatable_storage_setting' => 'a translatable field storage setting',
      'test_field_setting' => 'dummy test string',
      'translatable_field_setting' => 'a translatable field setting',
      'field_setting_from_config_data' => TRUE,
    ];
    $this->assertEqual($field->getSettings(), $expected_settings);

    // Change one single setting using setSettings(), and check that the other
    // expected settings are still present.
    $expected_settings['test_field_setting'] = 'another test string';
    $field->setSettings(['test_field_setting' => $expected_settings['test_field_setting']]);
    $this->assertEqual($field->getSettings(), $expected_settings);
  }

}
