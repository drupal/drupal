<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Constraint(
 *   id = "FullyValidatable",
 *   label = @Translation("Whether this config schema type is fully validatable", context = "Validation"),
 * )
 */
final class FullyValidatableConstraint extends Constraint {}
