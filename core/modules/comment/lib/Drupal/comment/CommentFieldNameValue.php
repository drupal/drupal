<?php

/**
 * @file
 * Contains \Drupal\comment\CommentNewValue.
 */

namespace Drupal\comment;

use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\ReadOnlyException;
use InvalidArgumentException;

/**
 * A computed property for the string value of the field_name field.
 */
class CommentFieldNameValue extends TypedData {

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue() {
    if (!isset($this->value)) {
      if (!isset($this->parent)) {
        throw new InvalidArgumentException('Computed properties require context for computation.');
      }
      $field = $this->parent->getParent();
      $entity = $field->getParent();
      $parts = explode('__', $entity->field_id->value);
      if ($parts && count($parts) == 2) {
        $this->value = end($parts);
      }
    }
    return $this->value;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value, $notify = TRUE) {
    if (isset($value)) {
      $this->field_name = $value;
      // Also set the field id.
      $field = $this->parent->getParent();
      $entity = $field->getParent();
      $entity->field_id = $entity->entity_type->value . '__' . $value;
    }
  }

}
