<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\DataType\UuidItem.
 */

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the 'uuid_field' entity field item.
 *
 * The field uses a newly generated UUID as default value.
 *
 * @DataType(
 *   id = "uuid_field",
 *   label = @Translation("UUID field item"),
 *   description = @Translation("An entity field containing a UUID."),
 *   list_class = "\Drupal\Core\Entity\Field\Field",
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
