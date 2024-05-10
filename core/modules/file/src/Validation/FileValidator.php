<?php

namespace Drupal\file\Validation;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\file\FileInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a class for file validation.
 */
class FileValidator implements FileValidatorInterface {

  /**
   * Creates a new FileValidator.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The validator.
   * @param \Drupal\Core\Validation\ConstraintManager $constraintManager
   *   The constraint factory.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected ValidatorInterface $validator,
    protected ConstraintManager $constraintManager,
    protected EventDispatcherInterface $eventDispatcher,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate(FileInterface $file, array $validators): ConstraintViolationListInterface {
    $constraints = [];
    foreach ($validators as $validator => $options) {
      // Create the constraint.
      // Options are an associative array of constraint properties and values.
      $constraints[] = $this->constraintManager->create($validator, $options);
    }

    // Get the typed data.
    $fileTypedData = $file->getTypedData();

    $violations = $this->validator->validate($fileTypedData, $constraints);

    $this->eventDispatcher->dispatch(new FileValidationEvent($file, $violations));

    // Always check the insecure upload constraint.
    if (count($violations) === 0) {
      $insecureUploadConstraint = $this->constraintManager->create('FileExtensionSecure', []);
      $violations = $this->validator->validate($fileTypedData, $insecureUploadConstraint);
    }

    return $violations;
  }

}
