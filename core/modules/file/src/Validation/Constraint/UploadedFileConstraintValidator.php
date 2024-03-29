<?php

declare(strict_types=1);

namespace Drupal\file\Validation\Constraint;

use Drupal\Component\Utility\Environment;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Constraint validator for uploaded files.
 *
 * Use FileValidatorInterface for validating file entities.
 *
 * @see \Drupal\Core\Validation\FileValidatorInterface
 */
class UploadedFileConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof UploadedFileConstraint) {
      throw new UnexpectedTypeException($constraint, UploadedFileConstraint::class);
    }
    if (!$value instanceof UploadedFile) {
      throw new UnexpectedTypeException($value, UploadedFile::class);
    }
    if ($value->isValid()) {
      return;
    }
    $maxSize = $constraint->maxSize ?? Environment::getUploadMaxSize();

    match ($value->getError()) {
      \UPLOAD_ERR_INI_SIZE => $this->context->buildViolation($constraint->uploadIniSizeErrorMessage, [
        '%file' => $value->getClientOriginalName(),
        '%maxsize' => ByteSizeMarkup::create($maxSize),
      ])->setCode((string) \UPLOAD_ERR_INI_SIZE)
        ->addViolation(),

      \UPLOAD_ERR_FORM_SIZE => $this->context->buildViolation($constraint->uploadFormSizeErrorMessage, [
        '%file' => $value->getClientOriginalName(),
        '%maxsize' => ByteSizeMarkup::create($maxSize),
      ])->setCode((string) \UPLOAD_ERR_FORM_SIZE)
        ->addViolation(),

      \UPLOAD_ERR_PARTIAL => $this->context->buildViolation($constraint->uploadPartialErrorMessage, [
        '%file' => $value->getClientOriginalName(),
      ])->setCode((string) \UPLOAD_ERR_PARTIAL)
        ->addViolation(),

      \UPLOAD_ERR_NO_FILE => $this->context->buildViolation($constraint->uploadNoFileErrorMessage, [
        '%file' => $value->getClientOriginalName(),
      ])->setCode((string) \UPLOAD_ERR_NO_FILE)
        ->addViolation(),

      default => $this->context->buildViolation($constraint->uploadErrorMessage, [
        '%file' => $value->getClientOriginalName(),
      ])->setCode((string) $value->getError())
        ->addViolation()
    };
  }

}
