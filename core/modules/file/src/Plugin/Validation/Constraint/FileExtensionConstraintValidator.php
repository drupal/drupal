<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the file extension constraint.
 */
class FileExtensionConstraintValidator extends BaseFileConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FileExtensionConstraint) {
      throw new UnexpectedTypeException($constraint, FileExtensionConstraint::class);
    }

    $extensions = $constraint->extensions;
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
    // Filename may differ from the basename, for instance in case files
    // migrated from D7 file entities. Because of that new files are saved
    // temporarily with a generated file name, without the original extension,
    // we will use the generated filename property for extension validation only
    // in case of temporary files; and use the file system file name in case of
    // permanent files.
    $subject = $file->isTemporary() ? $file->getFilename() : $file->getFileUri();
    if (!preg_match($regex, $subject)) {
      $this->context->addViolation($constraint->message, ['%files-allowed' => $extensions]);
    }
  }

}
