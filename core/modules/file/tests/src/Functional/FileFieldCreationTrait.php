<?php

namespace Drupal\Tests\file\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides methods for creating file fields.
 */
trait FileFieldCreationTrait {

  /**
   * Creates a new file field.
   *
   * @param string $name
   *   The name of the new field (all lowercase). The Field UI 'field_' prefix
   *   is not added to the field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The file field.
   */
  public function createFileField($name, $entity_type, $bundle, $storage_settings = [], $field_settings = [], $widget_settings = []) {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $name,
      'type' => 'file',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ]);
    $field_storage->save();

    $this->attachFileField($name, $entity_type, $bundle, $field_settings, $widget_settings);
    return $field_storage;
  }

  /**
   * Attaches a file field to an entity.
   *
   * @param string $name
   *   The name of the new field (all lowercase). The Field UI 'field_' prefix
   *   is not added to the field name.
   * @param string $entity_type
   *   The entity type this field will be added to.
   * @param string $bundle
   *   The bundle this field will be added to.
   * @param array $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  public function attachFileField($name, $entity_type, $bundle, $field_settings = [], $widget_settings = []) {
    $field = [
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ];
    FieldConfig::create($field)->save();

    \Drupal::service('entity_display.repository')->getFormDisplay($entity_type, $bundle)
      ->setComponent($name, [
        'type' => 'file_generic',
        'settings' => $widget_settings,
      ])
      ->save();
    // Assign display settings.
    \Drupal::service('entity_display.repository')->getViewDisplay($entity_type, $bundle)
      ->setComponent($name, [
        'label' => 'hidden',
        'type' => 'file_default',
      ])
      ->save();
  }

}
