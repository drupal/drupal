<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\NullConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Null;

/**
 * Null constraint.
 *
 * Overrides the symfony constraint to handle empty Typed Data structures.
 *
 * @Plugin(
 *   id = "Null",
 *   label = @Translation("Null", context = "Validation"),
 *   type = false
 * )
 */
class NullConstraint extends Null { }
