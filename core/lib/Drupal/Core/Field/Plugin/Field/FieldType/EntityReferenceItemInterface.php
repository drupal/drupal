<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Interface definition for field items referencing other entities.
 *
 * Field items should extend \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemBase.
 */
interface EntityReferenceItemInterface {

  /**
   * Returns the referenceable entity types and bundles.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which to retrieve the referenceable entity
   *   types and bundles.
   *
   * @return array
   *   An array of referenceable bundles where the array is keyed by the entity
   *   type ID, with values an array of bundle names. (It is a single-value
   *   array with the entity type ID if the entity type does not implement
   *   bundles.)
   */
  public static function getReferenceableBundles(FieldDefinitionInterface $field_definition): array;

}
