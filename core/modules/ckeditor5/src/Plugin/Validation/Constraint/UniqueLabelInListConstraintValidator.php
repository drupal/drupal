<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Uniquely labeled list item constraint validator.
 *
 * @internal
 */
class UniqueLabelInListConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($list, Constraint $constraint): void {
    if (!$constraint instanceof UniqueLabelInListConstraint) {
      throw new UnexpectedTypeException($constraint, UniqueLabelInListConstraint::class);
    }

    $labels = array_column($list, $constraint->labelKey);
    $label_frequencies = array_count_values($labels);

    foreach ($label_frequencies as $label => $frequency) {
      if ($frequency > 1) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%label', $label)
          ->addViolation();
      }
    }
  }

}
