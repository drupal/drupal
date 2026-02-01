<?php

namespace Drupal\Core\Path\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for unique path alias values.
 */
#[Constraint(
  id: 'UniquePathAlias',
  label: new TranslatableMarkup('Unique path alias.', [], ['context' => 'Validation'])
)]
class UniquePathAliasConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'The alias %alias is already in use in this language.',
    public $differentCapitalizationMessage = 'The alias %alias could not be added because it is already in use in this language with different capitalization: %stored_alias.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
