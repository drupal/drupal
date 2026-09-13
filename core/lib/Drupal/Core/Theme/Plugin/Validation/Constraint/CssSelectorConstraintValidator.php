<?php

declare(strict_types = 1);

namespace Drupal\Core\Theme\Plugin\Validation\Constraint;

use Drupal\Core\Theme\Entity\DesignTokenInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the CssSelector constraint.
 */
class CssSelectorConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof CssSelectorConstraint);

    if (!is_string($value)) {
      throw new UnexpectedValueException($value, 'string');
    }

    // In case it is a key that is tested.
    $testedValue = str_replace(DesignTokenInterface::DOT_CONVERSION_CHARACTER, '.', $value);

    // The goal of this regex is not validate the correctness of a CSS selector
    // but to check the string can only be a CSS selector, correct or not, by
    // prohibiting a full selector + property + value combination.
    if (preg_match('/[{};]/', $testedValue)) {
      $this->context->addViolation($constraint->message, ['@scope' => $value]);
    }
  }

}
