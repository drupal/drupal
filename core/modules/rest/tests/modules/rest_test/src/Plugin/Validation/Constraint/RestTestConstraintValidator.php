<?php

namespace Drupal\rest_test\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for \Drupal\rest_test\Plugin\Validation\Constraint\RestTestConstraint.
 */
class RestTestConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value instanceof FieldItemListInterface) {
      $value = $value->getValue();
      if (!empty($value[0]['value']) && $value[0]['value'] === 'ALWAYS_FAIL') {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
