<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates if a method on a service or instantiated object returns true.
 */
class ClassResolverConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(protected ClassResolver $classResolver) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {

    if (!$constraint instanceof ClassResolverConstraint) {
      throw new UnexpectedTypeException($constraint, ClassResolverConstraint::class);
    }
    $service = $this->classResolver->getInstanceFromDefinition($constraint->classOrService);

    if (!method_exists($service, $constraint->method)) {
      throw new \InvalidArgumentException('The method "' . $constraint->method . '" does not exist on the service "' . $constraint->classOrService . '".');
    }

    $result = $service->{$constraint->method}($value);
    if ($result !== TRUE) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('@classOrService', $constraint->classOrService)
        ->setParameter('@method', $constraint->method)
        ->setParameter('@value', $value)
        ->addViolation();
    }
  }

}
