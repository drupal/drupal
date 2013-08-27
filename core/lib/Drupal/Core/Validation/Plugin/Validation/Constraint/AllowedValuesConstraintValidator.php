<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraintValidator.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\AllowedValuesInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;

/**
 * Validates the AllowedValues constraint.
 */
class AllowedValuesConstraintValidator extends ChoiceValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($this->context->getMetadata()->getTypedData() instanceof AllowedValuesInterface) {
      $account = \Drupal::currentUser();
      $allowed_values = $this->context->getMetadata()->getTypedData()->getSettableValues($account);
      $constraint->choices = $allowed_values;
    }
    return parent::validate($value, $constraint);
  }
}
