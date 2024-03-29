<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if the plain text password is provided for editing a protected field.
 */
#[Constraint(
  id: 'ProtectedUserField',
  label: new TranslatableMarkup('Password required for protected field change', [], ['context' => 'Validation'])
)]
class ProtectedUserFieldConstraint extends SymfonyConstraint {

  /**
   * Violation message.
   *
   * @var string
   */
  public $message = "Your current password is missing or incorrect; it's required to change the %name.";

}
