<?php
/**
 * @file
 * Contains \Drupal\Core\TypedData\DataReferenceTargetDefinition.
 */

namespace Drupal\Core\TypedData;

/**
 * A typed data definition class for the entity reference target_id property.
 *
 * The target_id property differs from other data definitions in that it is
 * required at the storage level, but not at the validation level. This is
 * because its value can be set just-in-time using the preSave() method.
 *
 * Validation for the target_id property is provided by the 'ValidReference'
 * validation constraint.
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::preSave()
 */
class DataReferenceTargetDefinition extends DataDefinition {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    // If this data definition is marked as required for the sake of schema
    // definitions, we don't enforce it using the NotNull constraint. Instead
    // \Drupal\Core\Field\EntityReferenceItem is validated by the
    // 'ValidReference' constraint that operates at the field-item level. This
    // constraint takes into consideration that the target_id property can
    // be derived from the entity property.
    unset($constraints['NotNull']);
    return $constraints;
  }

}
