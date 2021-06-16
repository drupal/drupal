<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNullValidator;

/**
 * NotNull constraint validator.
 *
 * Overrides the symfony validator to handle empty Typed Data structures.
 */
class NotNullConstraintValidator extends NotNullValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();
    if (($typed_data instanceof ListInterface || $typed_data instanceof ComplexDataInterface) && $typed_data->isEmpty()) {
      $value = NULL;
    }
    parent::validate($value, $constraint);
  }

}
