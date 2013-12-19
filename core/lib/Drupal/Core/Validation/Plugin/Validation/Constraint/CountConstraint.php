<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\CountConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Count;

/**
 * Count constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @Plugin(
 *   id = "Count",
 *   label = @Translation("Count", context = "Validation"),
 *   type = { "list" }
 * )
 */
class CountConstraint extends Count {

  public $minMessage = 'This collection should contain %limit element or more.|This collection should contain %limit elements or more.';
  public $maxMessage = 'This collection should contain %limit element or less.|This collection should contain %limit elements or less.';
  public $exactMessage = 'This collection should contain exactly %limit element.|This collection should contain exactly %limit elements.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\CountValidator';
  }
}
