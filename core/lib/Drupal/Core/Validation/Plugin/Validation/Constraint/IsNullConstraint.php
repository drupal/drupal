<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;

/**
 * Null constraint.
 *
 * Overrides the symfony constraint to handle empty Typed Data structures.
 */
#[Constraint(
  id: 'Null',
  label: new TranslatableMarkup('Null', [], ['context' => 'Validation']),
  type: FALSE
)]
class IsNullConstraint extends IsNull {}
