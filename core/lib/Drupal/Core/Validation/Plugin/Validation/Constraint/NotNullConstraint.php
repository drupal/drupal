<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\NotNull;

/**
 * NotNull constraint.
 *
 * Overrides the symfony constraint to handle empty Typed Data structures.
 *
 * @Constraint(
 *   id = "NotNull",
 *   label = @Translation("NotNull", context = "Validation"),
 *   type = false
 * )
 */
class NotNullConstraint extends NotNull {}
