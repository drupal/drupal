<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\EmailConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Email;

/**
 * Count constraint.
 *
 * Overrides the symfony constraint to use the strict setting.
 *
 * @Plugin(
 *   id = "Email",
 *   label = @Translation("Email", context = "Validation")
 * )
 */
class EmailConstraint extends Email {

  public $strict = TRUE;

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\EmailValidator';
  }
}
