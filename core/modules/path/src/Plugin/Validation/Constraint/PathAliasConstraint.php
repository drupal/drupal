<?php

namespace Drupal\path\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for changing path aliases in pending revisions.
 */
#[Constraint(
  id: 'PathAlias',
  label: new TranslatableMarkup('Path alias.', [], ['context' => 'Validation'])
)]
class PathAliasConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'You can only change the URL alias for the <em>published</em> version of this content.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
