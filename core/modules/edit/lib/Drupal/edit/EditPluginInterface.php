<?php

/**
 * @file
 * Contains \Drupal\edit\EditPluginInterface.
 */

namespace Drupal\edit;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines an interface for in-place editors (Create.js PropertyEditor widgets).
 *
 * A PropertyEditor widget is a user-facing interface to edit an entity property
 * through Create.js.
 */
interface EditPluginInterface extends PluginInspectionInterface {

  /**
   * Checks whether this editor is compatible with a given field instance.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the field being edited.
   * @param array $items
   *   The field's item values.
   *
   * @return bool
   *   TRUE if it is compatible, FALSE otherwise.
   */
  public function isCompatible(FieldDefinitionInterface $field_definition, array $items);

  /**
   * Generates metadata that is needed specifically for this editor.
   *
   * Will only be called by \Drupal\edit\MetadataGeneratorInterface::generate()
   * when the passed in field instance & item values will use this editor.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the field being edited.
   * @param array $items
   *   The field's item values.
   *
   * @return array
   *   A keyed array with metadata. Each key should be prefixed with the plugin
   *   ID of the editor.
   */
  public function getMetadata(FieldDefinitionInterface $field_definition, array $items);

  /**
   * Returns the attachments for this editor.
   *
   * @return array
   *   An array of attachments, for use with #attached.
   *
   * @see drupal_process_attached()
   */
  public function getAttachments();
}
