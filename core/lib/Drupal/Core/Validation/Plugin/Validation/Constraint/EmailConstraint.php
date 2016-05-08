<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Email;

/**
 * Count constraint.
 *
 * Overrides the symfony constraint to use the strict setting.
 *
 * @Constraint(
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
