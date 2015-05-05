<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\RecursiveValidator.
 */

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines a recursive validator for Typed Data.
 *
 * The difference to \Symfony\Component\Validator\Validator\RecursiveValidator
 * is that we just allow to validate typed data objects.
 */
class RecursiveValidator implements ValidatorInterface {

  /**
   * @var \Symfony\Component\Validator\Context\ExecutionContextFactoryInterface
   */
  protected $contextFactory;

  /**
   * @var \Symfony\Component\Validator\ConstraintValidatorFactoryInterface
   */
  protected $constraintValidatorFactory;

  /**
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Creates a new validator.
   *
   * @param \Symfony\Component\Validator\Context\ExecutionContextFactoryInterface $context_factory
   *   The factory for creating new contexts.
   * @param \Symfony\Component\Validator\ConstraintValidatorFactoryInterface $validator_factory
   *   The constraint validator factory.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   The typed data manager.
   */
  public function __construct(ExecutionContextFactoryInterface $context_factory, ConstraintValidatorFactoryInterface $validator_factory, TypedDataManager $typed_data_manager) {
    $this->contextFactory = $context_factory;
    $this->constraintValidatorFactory = $validator_factory;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function startContext($root = NULL) {
    return new RecursiveContextualValidator($this->contextFactory->createContext($this, $root), $this, $this->constraintValidatorFactory, $this->typedDataManager);
  }

  /**
   * {@inheritdoc}
   */
  public function inContext(ExecutionContextInterface $context) {
    return new RecursiveContextualValidator($context, $this, $this->constraintValidatorFactory, $this->typedDataManager);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data
   *   A typed data object containing the value to validate.
   */
  public function getMetadataFor($typed_data) {
    if (!$typed_data instanceof TypedDataInterface) {
      throw new \InvalidArgumentException('The passed value must be a typed data object.');
    }
    return new TypedDataMetadata($typed_data);
  }

  /**
   * {@inheritdoc}
   */
  public function hasMetadataFor($value) {
    return $value instanceof TypedDataInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, $constraints = null, $groups = null) {
    return $this->startContext($value)
      ->validate($value, $constraints, $groups)
      ->getViolations();
  }

  /**
   * {@inheritdoc}
   */
  public function validateProperty($object, $propertyName, $groups = NULL) {
    return $this->startContext($object)
      ->validateProperty($object, $propertyName, $groups)
      ->getViolations();
  }

  /**
   * {@inheritdoc}
   */
  public function validatePropertyValue($objectOrClass, $propertyName, $value, $groups = NULL) {
    // Just passing a class name is not supported.
    if (!is_object($objectOrClass)) {
      throw new \LogicException('Typed data validation does not support passing the class name only.');
    }
    return $this->startContext($objectOrClass)
      ->validatePropertyValue($objectOrClass, $propertyName, $value, $groups)
      ->getViolations();
  }

}
