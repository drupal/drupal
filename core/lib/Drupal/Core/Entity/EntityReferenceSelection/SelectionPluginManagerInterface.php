<?php

namespace Drupal\Core\Entity\EntityReferenceSelection;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines an interface for the entity reference selection plugin manager.
 */
interface SelectionPluginManagerInterface extends PluginManagerInterface {

  /**
   * Gets the plugin ID for a given target entity type and base plugin ID.
   *
   * @param string $target_type
   *   The target entity type.
   * @param string $base_plugin_id
   *   The base plugin ID (e.g. 'default' or 'views').
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId($target_type, $base_plugin_id);

  /**
   * Gets the selection plugins that can reference a specific entity type.
   *
   * @param string $entity_type_id
   *   A Drupal entity type ID.
   *
   * @return array
   *   An array of selection plugins grouped by selection group.
   */
  public function getSelectionGroups($entity_type_id);

  /**
   * Gets the selection handler for a given entity_reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for the operation.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   (optional) The entity for the operation. Defaults to NULL.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   *   The selection plugin.
   */
  public function getSelectionHandler(FieldDefinitionInterface $field_definition, EntityInterface $entity = NULL);

}
