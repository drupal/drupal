<?php

declare(strict_types=1);

namespace Drupal\file\Validation;

use Drupal\Core\Validation\BasicRecursiveValidatorFactory;
use Drupal\file\Validation\Constraint\UploadedFileConstraint;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validator for uploaded files.
 */
class UploadedFileValidator implements UploadedFileValidatorInterface {

  /**
   * The symfony validator.
   *
   * @var \Symfony\Component\Validator\Validator\ValidatorInterface
   */
  protected ValidatorInterface $validator;

  /**
   * Creates a new UploadedFileValidator.
   *
   * @param \Drupal\Core\Validation\BasicRecursiveValidatorFactory $validatorFactory
   *   The validator factory.
   */
  public function __construct(
    protected readonly BasicRecursiveValidatorFactory $validatorFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate(UploadedFile $uploadedFile, array $options = []): ConstraintViolationListInterface {
    $constraint = new UploadedFileConstraint($options);
    return $this->getValidator()->validate($uploadedFile, $constraint);
  }

  /**
   * Get the Symfony validator instance.
   *
   * @return \Symfony\Component\Validator\Validator\ValidatorInterface
   *   The Symfony validator.
   */
  protected function getValidator(): ValidatorInterface {
    if (!isset($this->validator)) {
      $this->validator = $this->validatorFactory->createValidator();
    }
    return $this->validator;
  }

}
