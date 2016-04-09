<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Choice;

/**
 * Checks for the value being allowed.
 *
 * @Constraint(
 *   id = "AllowedValues",
 *   label = @Translation("Allowed values", context = "Validation")
 * )
 *
 * @see \Drupal\Core\TypedData\OptionsProviderInterface
 */
class AllowedValuesConstraint extends Choice {

  public $minMessage = 'You must select at least %limit choice.|You must select at least %limit choices.';
  public $maxMessage = 'You must select at most %limit choice.|You must select at most %limit choices.';
}
