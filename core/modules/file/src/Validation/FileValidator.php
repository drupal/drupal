<?php

namespace Drupal\file\Validation;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Core\Validation\DrupalTranslator;
use Drupal\file\FileInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
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
    $errors = [];
    foreach ($validators as $validator => $options) {
      if (function_exists($validator)) {
        @trigger_error('Support for file validation function ' . $validator . '() is deprecated in drupal:10.2.0 and will be removed in drupal:11.0.0. Use Symfony Constraints instead. See https://www.drupal.org/node/3363700', E_USER_DEPRECATED);
        if (!is_array($options)) {
          $options = [$options];
        }
        array_unshift($options, $file);
        // Call the validation function.
        // Options are a list of function args.
        $errors = array_merge($errors, call_user_func_array($validator, $options));
      }
      else {
        // Create the constraint.
        // Options are an associative array of constraint properties and values.
        try {
          $constraints[] = $this->constraintManager->create($validator, $options);
        }
        catch (PluginNotFoundException) {
          @trigger_error(sprintf('Passing invalid constraint plugin ID "%s" in the list of $validators to Drupal\file\Validation\FileValidator::validate() is deprecated in drupal:10.2.0 and will throw an exception in drupal:11.0.0. See https://www.drupal.org/node/3363700', $validator), E_USER_DEPRECATED);
        }
      }
    }

    // Call legacy hook implementations.
    $errors = array_merge($errors, $this->moduleHandler->invokeAllDeprecated('Use file validation events instead. See https://www.drupal.org/node/3363700', 'file_validate', [$file]));

    $violations = new ConstraintViolationList();

    // Convert legacy errors to violations.
    $translator = new DrupalTranslator();
    foreach ($errors as $error) {
      $violation = new ConstraintViolation($translator->trans($error),
        $error,
        [],
        $file,
        '',
        NULL
      );
      $violations->add($violation);
    }

    // Get the typed data.
    $fileTypedData = $file->getTypedData();

    $violations->addAll($this->validator->validate($fileTypedData, $constraints));

    $this->eventDispatcher->dispatch(new FileValidationEvent($file, $violations));

    // Always check the insecure upload constraint.
    if (count($violations) === 0) {
      $insecureUploadConstraint = $this->constraintManager->create('FileExtensionSecure', []);
      $violations = $this->validator->validate($fileTypedData, $insecureUploadConstraint);
    }

    return $violations;
  }

}
