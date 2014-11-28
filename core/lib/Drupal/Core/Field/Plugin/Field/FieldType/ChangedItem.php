<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\ChangedItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

/**
 * Defines the 'changed' entity field type.
 *
 * @FieldType(
 *   id = "changed",
 *   label = @Translation("Last changed"),
 *   description = @Translation("An entity field containing a UNIX timestamp of when the entity has been last updated."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\ChangedFieldItemList",
 *   constraints = {
 *     "ComplexData" = {"value" = {"EntityChanged" = {}}}
 *   }
 * )
 */
class ChangedItem extends CreatedItem {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $this->value = REQUEST_TIME;
  }

}
