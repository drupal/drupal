<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the Enum constraint.
 */
class EnumConstraintValidator extends ConstraintValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!isset($value)) {
      return;
    }

    if (!$constraint instanceof EnumConstraint) {
      throw new UnexpectedTypeException($constraint, EnumConstraint::class);
    }

    $class = $this->getTypedData()->getDataDefinition()['enum_class'] ?? '';
    if (empty($class)) {
      $this->context->addViolation($constraint->missingClassMessage);
      return;
    }

    if (!$value instanceof \UnitEnum) {
      $this->context->addViolation($constraint->notEnumMessage);
      return;
    }

    if (!$value instanceof $class) {
      $this->context->addViolation($constraint->invalidClassMessage, [
        '%class' => $class,
      ]);
    }
  }

}
