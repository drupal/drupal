<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Serialized constraint.
 */
class SerializedConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {

    if (!isset($value)) {
      return;
    }

    if (!is_string($value)) {
      $this->context->addViolation($constraint->wrongTypeMessage, ['{type}' => gettype($value)]);
      return;
    }

    // Unserialize will return false if unserializing "false". It will also
    // return false if unserialization fails. Handle this edge case.
    if ('b:0;' === $value) {
      return;
    }

    $unserialized = @unserialize($value, ['allowed_classes' => FALSE]);

    if ($unserialized === FALSE) {
      $this->context->addViolation($constraint->message);
    }
  }

}
