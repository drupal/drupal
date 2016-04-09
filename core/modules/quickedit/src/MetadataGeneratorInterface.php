<?php

namespace Drupal\quickedit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for generating in-place editing metadata.
 */
interface MetadataGeneratorInterface {

  /**
   * Generates in-place editing metadata for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity, in the language in which one of its fields is being edited.
   * @return array
   *   An array containing metadata with the following keys:
   *   - label: the user-visible label for the entity in the given language.
   */
  public function generateEntityMetadata(EntityInterface $entity);

  /**
   * Generates in-place editing metadata for an entity field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be in-place edited.
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
  public function generateFieldMetadata(FieldItemListInterface $items, $view_mode);

}
