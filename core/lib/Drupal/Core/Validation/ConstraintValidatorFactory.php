<?php

namespace Drupal\Core\Validation;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory as BaseConstraintValidatorFactory;

/**
 * Defines a constraint validator factory that works with container injection.
 */
class ConstraintValidatorFactory extends BaseConstraintValidatorFactory {

  /**
   * Constructs a new ConstraintValidatorFactory.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   */
  public function __construct(ClassResolverInterface $class_resolver) {
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(Constraint $constraint) {
    $class_name = $constraint->validatedBy();

    if (!isset($this->validators[$class_name])) {
      $this->validators[$class_name] = $this->classResolver->getInstanceFromDefinition($class_name);
    }

    return $this->validators[$class_name];
  }

}
