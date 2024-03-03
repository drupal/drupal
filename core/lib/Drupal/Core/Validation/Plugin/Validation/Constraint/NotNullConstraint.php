<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * NotNull constraint.
 *
 * Overrides the symfony constraint to handle empty Typed Data structures.
 */
#[Constraint(
  id: 'NotNull',
  label: new TranslatableMarkup('NotNull', [], ['context' => 'Validation']),
  type: FALSE
)]
class NotNullConstraint extends NotNull {}
