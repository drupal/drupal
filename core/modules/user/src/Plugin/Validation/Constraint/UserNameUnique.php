<?php

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Checks if a user name is unique on the site.
 */
#[Constraint(
  id: 'UserNameUnique',
  label: new TranslatableMarkup('User name unique', [], ['context' => 'Validation'])
)]
class UserNameUnique extends UniqueFieldConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?bool $caseSensitive = NULL,
    $message = 'The username %value is already taken.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $caseSensitive, $message, $groups, $payload);
  }

}
