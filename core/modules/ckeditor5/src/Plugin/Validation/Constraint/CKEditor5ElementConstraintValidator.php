<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * CKEditor 5 element validator.
 *
 * @internal
 */
class CKEditor5ElementConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($element, $constraint) {
    if (!$constraint instanceof CKEditor5ElementConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CKEditor5Element');
    }

    $parsed = HTMLRestrictions::fromString($element);
    if ($parsed->isEmpty() || count($parsed->getAllowedElements()) > 1 || $element !== $parsed->toCKEditor5ElementsArray()[0]) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%provided_element', $element)
        ->addViolation();
    }
  }

}
