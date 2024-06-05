<?php

namespace Drupal\Core\Validation;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory as BaseConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;

/**
 * Defines a constraint validator factory that works with container injection.
 *
 * @todo Decide what to do with this class or how to reuse constraint
 * validators in https://drupal.org/project/drupal/issues/3097071
 */
class ConstraintValidatorFactory extends BaseConstraintValidatorFactory {

  /**
   * The class resolver.
   */
  protected ClassResolverInterface $classResolver;

  /**
   * Constructs a new ConstraintValidatorFactory.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(ClassResolverInterface $class_resolver) {
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(Constraint $constraint): ConstraintValidatorInterface {
    $class_name = $constraint->validatedBy();
    // Constraint validator instances should always be initialized newly and
    // never shared, because the current validation context is getting injected
    // into them through setter injection and in a case of a recursive
    // validation where a validator triggers a validation chain leading to the
    // same validator the context of the first call would be exchanged with the
    // one of the subsequent validation chain.
    return $this->classResolver->getInstanceFromDefinition($class_name);
  }

}
