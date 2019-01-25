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
    // Since Symfony 4.1, the 'mode' property is used, for previous versions the
    // 'strict' property. If the 'strict' property is set,
    // \Symfony\Component\Validator\Constraints\EmailValidator will trigger
    // a deprecation error, so only assign a value for versions of Symfony
    // < 4.2. This compatibility layer can be removed once Drupal requires
    // Symfony 4.2 or higher in https://www.drupal.org/node/3009219.
    if (property_exists($this, 'mode')) {
      $default_options = ['mode' => 'strict'];
    }
    else {
      $default_options = ['strict' => TRUE];
    }
    $options += $default_options;
    parent::__construct($options);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\EmailValidator';
  }

}
