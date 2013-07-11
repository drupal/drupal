<?php

/**
 * @file
 * Contains \Drupal\edit\MetadataGeneratorInterface.
 */

namespace Drupal\edit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;

/**
 * Interface for generating in-place editing metadata.
 */
interface MetadataGeneratorInterface {

  /**
   * Generates in-place editing metadata for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param string $langcode
   *   The name of the language for which the field is being edited.
   * @return array
   *   An array containing metadata with the following keys:
   *   - label: the user-visible label for the entity in the given language.
   */
  public function generateEntity(EntityInterface $entity, $langcode);

  /**
   * Generates in-place editing metadata for an entity field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition
   *   The field definition of the field being edited.
   * @param string $langcode
   *   The name of the language for which the field is being edited.
   * @param string $view_mode
   *   The view mode the field should be rerendered in.
   * @return array
   *   An array containing metadata with the following keys:
   *   - label: the user-visible label for the field.
   *   - access: whether the current user may edit the field or not.
   *   - editor: which editor should be used for the field.
   *   - aria: the ARIA label.
   *   - custom: (optional) any additional metadata that the editor provides.
   */
  public function generateField(EntityInterface $entity, FieldDefinitionInterface $field_definition, $langcode, $view_mode);

}
