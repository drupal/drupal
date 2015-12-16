<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\LengthConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Length;

/**
 * Length constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @todo: Move this below the TypedData core component.
 *
 * @Constraint(
 *   id = "Length",
 *   label = @Translation("Length", context = "Validation"),
 *   type = { "string" }
 * )
 */
class LengthConstraint extends Length {

  public $maxMessage = 'This value is too long. It should have %limit character or less.|This value is too long. It should have %limit characters or less.';
  public $minMessage = 'This value is too short. It should have %limit character or more.|This value is too short. It should have %limit characters or more.';
  public $exactMessage = 'This value should have exactly %limit character.|This value should have exactly %limit characters.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\LengthValidator';
  }
}
