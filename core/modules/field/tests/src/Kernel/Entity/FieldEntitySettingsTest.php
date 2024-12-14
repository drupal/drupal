<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ways that field entities handle their settings.
 *
 * @group field
 */
class FieldEntitySettingsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_with_bundle');
    EntityTestBundle::create(['id' => 'test', 'label' => 'Test'])->save();
  }

  /**
   * @group legacy
   */
  public function testFieldEntitiesCarryDefaultSettings(): void {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = FieldStorageConfig::create([
      'type' => 'integer',
      'entity_type' => 'entity_test_with_bundle',
      'field_name' => 'test',
    ]);
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test',
    ]);

    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.field.field_type');
    $default_storage_settings = $plugin_manager->getDefaultStorageSettings('integer');
    $default_field_settings = $plugin_manager->getDefaultFieldSettings('integer');

    // Both entities should have the complete, default settings for their
    // field type.
    $this->assertSame($default_storage_settings, $field_storage->get('settings'));
    $this->assertSame($default_field_settings, $field->get('settings'));

    // If we try to set incomplete settings, the existing values should be
    // retained.
    $storage_settings = $field_storage->setSettings(['size' => 'big'])
      ->get('settings');
    // There should be no missing settings.
    $missing_storage_settings = array_diff_key($default_storage_settings, $storage_settings);
    $this->assertEmpty($missing_storage_settings);
    // The value we set should be remembered.
    $this->assertSame('big', $storage_settings['size']);

    $field_settings = $field->setSetting('min', 10)->getSettings();
    $missing_field_settings = array_diff_key($default_field_settings, $field_settings);
    $this->assertEmpty($missing_field_settings);
    $this->assertSame(10, $field_settings['min']);

    $field_settings = $field->setSettings(['max' => 39])->get('settings');
    $missing_field_settings = array_diff_key($default_field_settings, $field_settings);
    $this->assertEmpty($missing_field_settings);
    $this->assertSame(39, $field_settings['max']);

    // Test that saving settings with incomplete settings is not triggering
    // error, and values are retained.
    $field_storage->save();
    $missing_storage_settings = array_diff_key($default_storage_settings, $storage_settings);
    $this->assertEmpty($missing_storage_settings);
    // The value we set should be remembered.
    $this->assertSame('big', $storage_settings['size']);

    $field->save();
    $missing_field_settings = array_diff_key($default_field_settings, $field_settings);
    $this->assertEmpty($missing_field_settings);
    $this->assertSame(39, $field_settings['max']);
  }

  /**
   * Tests entity reference settings are normalized on field creation and save.
   */
  public function testEntityReferenceSettingsNormalized(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_reference',
      'type' => 'entity_reference',
      'entity_type' => 'entity_test_with_bundle',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'entity_test_with_bundle',
      ],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test',
      'label' => 'Test Reference',
      'settings' => [
        'handler' => 'default',
      ],
    ]);
    $this->assertSame('default:entity_test_with_bundle', $field->getSetting('handler'));
    // If the handler is changed, it should be normalized again on pre-save.
    $field->setSetting('handler', 'default')->save();
    $this->assertSame('default:entity_test_with_bundle', $field->getSetting('handler'));
  }

}
