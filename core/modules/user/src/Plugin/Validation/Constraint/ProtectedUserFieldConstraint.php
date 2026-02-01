<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if the plain text password is provided for editing a protected field.
 */
#[Constraint(
  id: 'ProtectedUserField',
  label: new TranslatableMarkup('Password required for protected field change', [], ['context' => 'Validation'])
)]
class ProtectedUserFieldConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = "Your current password is missing or incorrect; it's required to change the %name.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
