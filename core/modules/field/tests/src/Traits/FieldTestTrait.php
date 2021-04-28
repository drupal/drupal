<?php

namespace Drupal\Tests\field\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides helpers to create and update fields in test setup.
 */
trait FieldTestTrait {

  /**
   * Creates a new field via the API.
   *
   * @param string $entity_type
   *   The entity type to add a field to.
   * @param string $bundle_name
   *   The bundle to add an instance to.
   * @param string $field_name
   *   The name of the field.
   * @param string $field_type
   *   The type of the field.
   */
  public function fieldAddNewField($entity_type, $bundle_name, $field_name, $field_type) {
    // TODO: storage options, field options
    // TODO: non-default widget and formatter types

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      // TODO - get this from the storage options.
      'cardinality' => '1',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle_name,
    ]);
    $field->save();

    // TODO: only throw these exceptions if no parameters were passed for the
    // widget and formatter types.
    $field_type_definition = $this->container->get('plugin.manager.field.field_type')->getDefinition($field_type);
    if (!isset($field_type_definition['default_widget'])) {
      throw new \Exception("The field type $field_type has no default widget, and no widget type was given.");
    }
    if (!isset($field_type_definition['default_formatter'])) {
      throw new \Exception("The field type $field_type has no default formatter, and no formatter type was given.");
    }

    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay($entity_type, $bundle_name);
    $form_display = $form_display->setComponent($field_name, ['type' => $field_type_definition['default_widget']]);
    $form_display->save();

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay($entity_type, $bundle_name);
    $view_display->setComponent($field_name, ['type' => $field_type_definition['default_formatter']]);
    $view_display->save();
  }

}
