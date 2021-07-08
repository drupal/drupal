<?php

namespace Drupal\migrate;

/**
 * The entity field definition trait.
 */
trait EntityFieldDefinitionTrait {

  /**
   * Gets the field definition from a specific entity base field.
   *
   * The method takes the field ID as an argument and returns the field storage
   * definition to be used in getIds() by querying the destination entity base
   * field definition.
   *
   * @param string $key
   *   The field ID key.
   *
   * @return array
   *   An associative array with a structure that contains the field type, keyed
   *   as 'type', together with field storage settings as they are returned by
   *   FieldStorageDefinitionInterface::getSettings().
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface::getSettings()
   */
  protected function getDefinitionFromEntity($key) {
    $plugin_id = $this->getPluginId();
    $entity_type_id = $this->getEntityTypeId($plugin_id);
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $definitions */
    $definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $field_definition = $definitions[$key];

    return [
      'type' => $field_definition->getType(),
    ] + $field_definition->getSettings();
  }

  /**
   * Finds the entity type from configuration or plugin ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return string
   *   The entity type.
   */
  protected static function getEntityTypeId($plugin_id) {
    $entity_type_id = NULL;
    if (strpos($plugin_id, static::DERIVATIVE_SEPARATOR)) {
      [, $entity_type_id] = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 2);
    }
    return $entity_type_id;
  }

}
