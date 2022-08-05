<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\Validation\ExecutionContext;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * A constraint may need preceding constraints to not have been violated.
 *
 * @internal
 */
trait PrecedingConstraintAwareValidatorTrait {

  /**
   * Checks whether any preceding constraints have been violated.
   *
   * @param \Symfony\Component\Validator\Constraint $current_constraint
   *   The constraint currently being validated.
   *
   * @return bool
   *   TRUE if any preceding constraints have been violated, FALSE otherwise.
   */
  protected function hasViolationsForPrecedingConstraints(Constraint $current_constraint): bool {
    assert($this->context instanceof ExecutionContext);
    $earlier_constraints = iterator_to_array($this->getPrecedingConstraints($current_constraint));
    $earlier_violations = array_filter(
      iterator_to_array($this->context->getViolations()),
      function (ConstraintViolationInterface $violation) use ($earlier_constraints) {
        return in_array($violation->getConstraint(), $earlier_constraints);
      }
    );
    return !empty($earlier_violations);
  }

  /**
   * Gets the constraints preceding the given constraint in the current context.
   *
   * @param \Symfony\Component\Validator\Constraint $needle
   *   The constraint to find the preceding constraints for.
   *
   * @return iterable
   *   The preceding constraints.
   */
  private function getPrecedingConstraints(Constraint $needle): iterable {
    assert($this->context instanceof ExecutionContext);
    $constraints = $this->context->getMetadata()->getConstraints();
    if (!in_array($needle, $constraints)) {
      throw new \OutOfBoundsException();
    }
    foreach ($constraints as $constraint) {
      if ($constraint != $needle) {
        yield $constraint;
      }
    }
  }

}
