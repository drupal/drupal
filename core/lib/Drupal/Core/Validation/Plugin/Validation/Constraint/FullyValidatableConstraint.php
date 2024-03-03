<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Constraint for fully validatable config schema type.
 */
#[Constraint(
  id: 'FullyValidatable',
  label: new TranslatableMarkup('Whether this config schema type is fully validatable', [], ['context' => 'Validation'])
)]
final class FullyValidatableConstraint extends SymfonyConstraint {}
