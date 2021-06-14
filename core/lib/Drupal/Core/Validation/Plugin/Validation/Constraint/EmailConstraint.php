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

  /**
   * {@inheritdoc}
   */
  public function __construct($options = []) {
    $options += ['mode' => 'strict'];
    parent::__construct($options);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\EmailValidator';
  }

}
