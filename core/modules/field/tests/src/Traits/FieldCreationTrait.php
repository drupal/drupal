<?php

namespace Drupal\Tests\field\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides helpers to create and update fields in test setup.
 */
trait FieldCreationTrait {

  /**
   * Creates a new field via the API.
   *
   * @param string $name
   *   The name of the new field (all lowercase).
   * @param string $field_type
   *   The field type.
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
  protected function fieldAddNewField($field_name, $field_type, $entity_type, $bundle_name, $storage_settings = [], $field_settings = [], $widget_settings = []) {
    // todo: non-default widget and formatter types
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      'settings' => $storage_settings,
      'cardinality' => $storage_settings['cardinality'] ?? 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ]);
    $field->save();

    $field_type_definition = $this->container->get('plugin.manager.field.field_type')->getDefinition($field_type);
    if (!isset($field_type_definition['default_widget'])) {
      throw new \Exception("The field type $field_type has no default widget, and no widget type was given.");
    }
    if (!isset($field_type_definition['default_formatter'])) {
      throw new \Exception("The field type $field_type has no default formatter, and no formatter type was given.");
    }

    $form_display = $this->container->get('entity_display.repository')->getFormDisplay($entity_type, $bundle_name);
    $form_display->setComponent($field_name, [
        'type' => $field_type_definition['default_widget'],
        'settings' => $widget_settings,
      ])
      ->save();

    $view_display = $this->container->get('entity_display.repository')->getViewDisplay($entity_type, $bundle_name);
    $view_display->setComponent($field_name, [
        'type' => $field_type_definition['default_formatter'],
      ])
      ->save();

    return $field_storage;
  }

}
