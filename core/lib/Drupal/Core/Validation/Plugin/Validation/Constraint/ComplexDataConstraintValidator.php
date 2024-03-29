<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates complex data.
 */
class ComplexDataConstraintValidator extends ConstraintValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($data, Constraint $constraint): void {

    // If un-wrapped data has been passed, fetch the typed data object first.
    if (!$data instanceof TypedDataInterface) {
      $data = $this->getTypedData();
    }
    if (!$data instanceof ComplexDataInterface) {
      throw new UnexpectedTypeException($data, 'ComplexData');
    }

    foreach ($constraint->properties as $name => $constraints) {
      $this->context->getValidator()
        ->inContext($this->context)
        // Specifically pass along FALSE as $root_call, as we validate the data
        // as part of the typed data tree.
        ->validate($data->get($name), $constraints, NULL, FALSE);
    }
  }

}
