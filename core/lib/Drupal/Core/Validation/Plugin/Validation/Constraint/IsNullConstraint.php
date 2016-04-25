<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\IsNull;

/**
 * Null constraint.
 *
 * Overrides the symfony constraint to handle empty Typed Data structures.
 *
 * @Constraint(
 *   id = "Null",
 *   label = @Translation("Null", context = "Validation"),
 *   type = false
 * )
 */
class IsNullConstraint extends IsNull { }
