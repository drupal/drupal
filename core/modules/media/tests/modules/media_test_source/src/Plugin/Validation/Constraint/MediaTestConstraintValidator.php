<?php

namespace Drupal\media_test_source\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the MediaTestConstraint.
 */
class MediaTestConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value instanceof EntityInterface) {
      $string_to_test = $value->label();
    }
    elseif ($value instanceof FieldItemListInterface) {
      $string_to_test = $value->value;
    }
    else {
      return;
    }

    if (!str_contains($string_to_test, 'love Drupal')) {
      $this->context->addViolation($constraint->message);
    }
  }

}
