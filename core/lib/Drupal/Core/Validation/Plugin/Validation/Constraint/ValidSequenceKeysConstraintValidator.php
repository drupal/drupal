<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Validation\ConstraintValidatorFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that all the keys of a sequence match the specified constraints.
 */
class ValidSequenceKeysConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    private readonly ClassResolverInterface $classResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof ValidSequenceKeysConstraint);

    if (!is_array($value)) {
      throw new UnexpectedTypeException($value, 'array');
    }

    if (empty($value)) {
      return;
    }

    $previousViolationCount = count($this->context->getViolations());
    $constraintValidatorFactory = new ConstraintValidatorFactory($this->classResolver);

    foreach (array_keys($value) as $sequence_key) {
      foreach ($constraint->constraints as $item) {
        $validator = $constraintValidatorFactory->getInstance($item);
        $validator->initialize($this->context);
        $validator->validate($sequence_key, $item);
      }
    }

    if (count($this->context->getViolations()) > $previousViolationCount) {
      $this->context->buildViolation($constraint->message)->addViolation();
    }
  }

}
