<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Range;

/**
 * Range constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @todo: Move this below the TypedData core component.
 *
 * @Constraint(
 *   id = "Range",
 *   label = @Translation("Range", context = "Validation"),
 *   type = { "integer", "float" }
 * )
 */
class RangeConstraint extends Range {

  public $minMessage = 'This value should be %limit or more.';
  public $maxMessage = 'This value should be %limit or less.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\RangeValidator';
  }

}
