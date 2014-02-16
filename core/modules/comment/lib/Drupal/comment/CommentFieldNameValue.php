<?php

/**
 * @file
 * Contains \Drupal\comment\CommentFieldNameValue.
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
   * {@inheritdoc}
   */
  public function getValue() {
    if (!isset($this->value)) {
      if (!isset($this->parent)) {
        throw new InvalidArgumentException('Computed properties require context for computation.');
      }
      $field = $this->parent->getParent();
      $entity = $field->getParent();
      // Field id is of the form {entity_type}__{field_name}. We set the
      // optional limit param to explode() in case the user adds a field with __
      // in the name.
      $parts = explode('__', $entity->getFieldId(), 2);
      if ($parts && count($parts) == 2) {
        $this->value = end($parts);
      }
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    if (isset($value)) {
      $this->field_name = $value;
      // Also set the field id.
      $field = $this->parent->getParent();
      $entity = $field->getParent();
      $entity->field_id = $entity->getCommentedEntityTypeId() . '__' . $value;
    }
  }

}
