<?php

namespace Drupal\Core\Field;

/**
 * Interface for entity reference lists of field items.
 */
interface EntityReferenceFieldItemListInterface extends FieldItemListInterface {

  /**
   * Gets the entities referenced by this field, preserving field item deltas.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects keyed by field item deltas.
   */
  public function referencedEntities();

}
