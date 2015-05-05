<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\NotNull;

/**
 * NotNull constraint.
 *
 * Overrides the symfony constraint to handle empty Typed Data structures.
 *
 * @Plugin(
 *   id = "NotNull",
 *   label = @Translation("NotNull", context = "Validation"),
 *   type = false
 * )
 */
class NotNullConstraint extends NotNull { }
