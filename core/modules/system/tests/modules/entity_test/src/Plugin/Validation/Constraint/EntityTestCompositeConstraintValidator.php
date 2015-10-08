<?php

/**
 * @file
 * Contains \Drupal\entity_test\Plugin\Validation\Constraint\EntityTestCompositeConstraintValidator.
 */

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for the EntityTestComposite constraint.
 */
class EntityTestCompositeConstraintValidator extends ConstraintValidator {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    if ($entity->name->value === 'test' && $entity->type->value === 'test2') {
      $this->context->buildViolation($constraint->message)
        ->atPath('type')
        ->addViolation();
    }
    if ($entity->name->value === 'failure-field-name') {
      $this->context->buildViolation('Name field violation')
        ->atPath('name')
        ->addViolation();
    }
    elseif ($entity->name->value === 'failure-field-type') {
      $this->context->buildViolation('Type field violation')
        ->atPath('type')
        ->addViolation();
    }
  }

}
