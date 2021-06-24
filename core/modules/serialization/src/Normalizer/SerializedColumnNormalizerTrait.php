<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * A trait providing methods for serialized columns.
 */
trait SerializedColumnNormalizerTrait {

  /**
   * Checks if there is a serialized string for a column.
   *
   * @param mixed $data
   *   The field item data to denormalize.
   * @param string $class
   *   The expected class to instantiate.
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   */
  protected function checkForSerializedStrings($data, $class, FieldItemInterface $field_item) {
    // Require specialized denormalizers for fields with 'serialize' columns.
    // Note: this cannot be checked in ::supportsDenormalization() because at
    // that time we only have the field item class. ::hasSerializeColumn()
    // must be able to call $field_item->schema(), which requires a field
    // storage definition. To determine that, the entity type and bundle
    // must be known, which is contextual information that the Symfony
    // serializer does not pass to ::supportsDenormalization().
    if (!is_array($data)) {
      $data = [$field_item->getDataDefinition()->getMainPropertyName() => $data];
    }
    if ($this->dataHasStringForSerializeColumn($field_item, $data)) {
      $field_name = $field_item->getParent() ? $field_item->getParent()->getName() : $field_item->getName();
      throw new \LogicException(sprintf('The generic FieldItemNormalizer cannot denormalize string values for "%s" properties of the "%s" field (field item class: %s).', implode('", "', $this->getSerializedPropertyNames($field_item)), $field_name, $class));
    }
  }

  /**
   * Checks if the data contains string value for serialize column.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   * @param array $data
   *   The data being denormalized.
   *
   * @return bool
   *   TRUE if there is a string value for serialize column, otherwise FALSE.
   */
  protected function dataHasStringForSerializeColumn(FieldItemInterface $field_item, array $data) {
    foreach ($this->getSerializedPropertyNames($field_item) as $property_name) {
      if (isset($data[$property_name]) && is_string($data[$property_name])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the names of all serialized properties.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   *
   * @return string[]
   *   The property names for serialized properties.
   */
  protected function getSerializedPropertyNames(FieldItemInterface $field_item) {
    $field_storage_definition = $field_item->getFieldDefinition()->getFieldStorageDefinition();

    if ($custom_property_names = $this->getCustomSerializedPropertyNames($field_item)) {
      return $custom_property_names;
    }

    $field_storage_schema = $field_item->schema($field_storage_definition);
    // If there are no columns then there are no serialized properties.
    if (!isset($field_storage_schema['columns'])) {
      return [];
    }
    $serialized_columns = array_filter($field_storage_schema['columns'], function ($column_schema) {
      return isset($column_schema['serialize']) && $column_schema['serialize'] === TRUE;
    });
    return array_keys($serialized_columns);
  }

  /**
   * Gets the names of all properties the plugin treats as serialized data.
   *
   * This allows the field storage definition or entity type to provide a
   * setting for serialized properties. This can be used for fields that
   * handle serialized data themselves and do not rely on the serialized schema
   * flag.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   *
   * @return string[]
   *   The property names for serialized properties.
   */
  protected function getCustomSerializedPropertyNames(FieldItemInterface $field_item) {
    if ($field_item instanceof PluginInspectionInterface) {
      $definition = $field_item->getPluginDefinition();
      $serialized_fields = $field_item->getEntity()->getEntityType()->get('serialized_field_property_names');
      $field_name = $field_item->getFieldDefinition()->getName();
      if (is_array($serialized_fields) && isset($serialized_fields[$field_name]) && is_array($serialized_fields[$field_name])) {
        return $serialized_fields[$field_name];
      }
      if (isset($definition['serialized_property_names']) && is_array($definition['serialized_property_names'])) {
        return $definition['serialized_property_names'];
      }
    }
    return [];
  }

}
