<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\field\field_type\UuidItem.
 */

namespace Drupal\Core\Entity\Plugin\field\field_type;

/**
 * Defines the 'uuid' entity field type.
 *
 * The field uses a newly generated UUID as default value.
 *
 * @FieldType(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   description = @Translation("An entity field containing a UUID."),
 *   configurable = FALSE,
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {"Length" = {"max" = 128}}
 *     }
 *   }
 * )
 */
class UuidItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to one field item with a generated UUID.
    $uuid = \Drupal::service('uuid');
    $this->setValue(array('value' => $uuid->generate()), $notify);
    return $this;
  }
}
