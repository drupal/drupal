<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the file encoding constraint.
 */
class FileEncodingConstraintValidator extends BaseFileConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {

    /** @var \Drupal\file\Entity\FileInterface $file */
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileEncodingConstraint) {
      throw new UnexpectedTypeException($constraint, FileEncodingConstraint::class);
    }

    $encodings = $constraint->encodings;
    $data = file_get_contents($file->getFileUri());
    foreach ($encodings as $encoding) {
      $this->validateEncoding($data, $encoding, $constraint);
    }
  }

  /**
   * Validates the encoding of the file.
   *
   * @param string $data
   *   The file data.
   * @param string $encoding
   *   The encoding to validate.
   * @param \Drupal\file\Plugin\Validation\Constraint\FileEncodingConstraint $constraint
   *   The constraint.
   */
  protected function validateEncoding(string $data, string $encoding, FileEncodingConstraint $constraint): void {
    if (mb_check_encoding($data, $encoding)) {
      return;
    }
    $this->context->addViolation($constraint->message, [
      '%encoding' => $encoding,
      '%detected' => mb_detect_encoding($data),
    ]);
  }

}
