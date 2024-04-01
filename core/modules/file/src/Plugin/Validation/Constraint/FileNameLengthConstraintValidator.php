<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the file name length constraint.
 */
class FileNameLengthConstraintValidator extends BaseFileConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileNameLengthConstraint) {
      throw new UnexpectedTypeException($constraint, FileNameLengthConstraint::class);
    }

    if (!$file->getFilename()) {
      $this->context->addViolation($constraint->messageEmpty);
    }
    if (mb_strlen($file->getFilename()) > $constraint->maxLength) {
      $this->context->addViolation($constraint->messageTooLong, [
        '%maxLength' => $constraint->maxLength,
      ]);
    }
  }

}
