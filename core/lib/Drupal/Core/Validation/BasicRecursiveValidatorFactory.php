<?php

declare(strict_types=1);

namespace Drupal\Core\Validation;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Validator\RecursiveValidator;

/**
 * A factory for creating Symfony recursive validators.
 */
class BasicRecursiveValidatorFactory {

  /**
   * Constructs a new RecursiveValidatorFactory.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver.
   */
  public function __construct(
    protected readonly ClassResolverInterface $classResolver,
  ) {}

  /**
   * Creates a new RecursiveValidator.
   *
   * @return \Symfony\Component\Validator\Validator\RecursiveValidator
   *   The validator.
   */
  public function createValidator(): RecursiveValidator {
    return new RecursiveValidator(
      new ExecutionContextFactory(new DrupalTranslator()),
      new LazyLoadingMetadataFactory(),
      new ConstraintValidatorFactory($this->classResolver),
    );
  }

}
