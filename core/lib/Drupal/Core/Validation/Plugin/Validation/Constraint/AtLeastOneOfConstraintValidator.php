<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the AtLeastOneOf constraint.
 */
class AtLeastOneOfConstraintValidator extends ConstraintValidator {

  /**
   * Validate a set of constraints against a value.
   *
   * This validator method is a copy of Symfony's AtLeastOneOf constraint. This
   * is necessary because Drupal does not support validation groups.
   *
   * @param mixed $value
   *   The value to validate.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint to validate against.
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof AtLeastOneOfConstraint) {
      throw new UnexpectedTypeException($constraint, AtLeastOneOfConstraint::class);
    }

    $validator = $this->context->getValidator();

    // Build a first violation to have the base message of the constraint.
    $baseMessageContext = clone $this->context;
    $baseMessageContext->buildViolation($constraint->message)->addViolation();
    $baseViolations = $baseMessageContext->getViolations();
    $messages = [(string) $baseViolations->get(\count($baseViolations) - 1)->getMessage()];

    foreach ($constraint->constraints as $key => $item) {
      $context_group = $this->context->getGroup();
      if (!\in_array($context_group, $item->groups, TRUE)) {
        continue;
      }

      $context = $this->context;
      $executionContext = clone $this->context;
      $executionContext->setNode($value, $this->context->getObject(), $this->context->getMetadata(), $this->context->getPropertyPath());
      $violations = $validator->inContext($executionContext)->validate($context->getObject(), $item/*, $context_group*/)->getViolations();
      $this->context = $context;

      if (\count($this->context->getViolations()) === \count($violations)) {
        return;
      }

      if ($constraint->includeInternalMessages) {
        $message = ' [' . ($key + 1) . '] ';

        if ($item instanceof All || $item instanceof Collection) {
          $message .= $constraint->messageCollection;
        }
        else {
          $message .= $violations->get(\count($violations) - 1)->getMessage();
        }

        $messages[] = $message;
      }
    }

    $this->context
      ->buildViolation(implode('', $messages))
      ->addViolation();
  }

}
