<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\RecursiveContextualValidatorInterface.
 */

namespace Drupal\Core\TypedData\Validation;

use Symfony\Component\Validator\Validator\ContextualValidatorInterface as ContextualValidatorInterfaceBase;

/**
 * Extends the contextual validator validate method by a new parameter.
 */
interface ContextualValidatorInterface extends ContextualValidatorInterfaceBase {

  /**
   * Validates a value against a constraint or a list of constraints.
   *
   * If no constraint is passed, the constraint
   * \Symfony\Component\Validator\Constraints\Valid is assumed.
   *
   * @param mixed $value
   *   The value to validate
   * @param \Symfony\Component\Validator\Constraint|\Symfony\Component\Validator\Constraint[] $constraints
   *   The constraint(s) to validate against.
   * @param array|null $groups
   *   The validation groups to validate, defaults to "Default".
   * @param bool $is_root_call
   *   (optional) Whether its the most upper call in the typed data tree.
   *
   * @see \Symfony\Component\Validator\Constraints\Valid
   *
   * @return $this
   */
  public function validate($value, $constraints = NULL, $groups = NULL, $is_root_call = TRUE);

}
