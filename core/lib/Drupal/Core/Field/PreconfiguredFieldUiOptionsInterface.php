<?php

/**
 * @file
 * Contains \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface.
 */

namespace Drupal\Core\Field;

/**
 * Defines an interface for exposing "preconfigured" field definitions.
 *
 * These field definitions will be exposed as additional options in the 'Add
 * field' form in Field UI, together with individual field types.
 *
 * @see \Drupal\Core\Field\FieldTypePluginManager::getUiDefinitions()
 * @see \Drupal\field_ui\Form\FieldStorageAddForm::submitForm()
 */
interface PreconfiguredFieldUiOptionsInterface {

  /**
   * Returns preconfigured field options for a field type.
   *
   * @return mixed[][]
   *   A multi-dimensional array with string keys and the following structure:
   *   - label: The label to show in the field type selection list.
   *   - category: (optional) The category in which to put the field label.
   *     Defaults to the category of the field type.
   *   - field_storage_config: An array with the following supported keys:
   *     - cardinality: The field cardinality.
   *     - settings: Field-type specific storage settings.
   *   - field_config: An array with the following supported keys:
   *     - required: Indicates whether the field is required.
   *     - settings: Field-type specific settings.
   *   - entity_form_display: An array with the following supported keys:
   *     - type: The widget to be used in the 'default' form mode.
   *   - entity_view_display: An array with the following supported keys:
   *     - type: The formatter to be used in the 'default' view mode.
   *
   * @see \Drupal\field\Entity\FieldStorageConfig
   * @see \Drupal\field\Entity\FieldConfig
   * @see \Drupal\Core\Entity\Display\EntityDisplayInterface::setComponent()
   */
  public static function getPreconfiguredOptions();

}
