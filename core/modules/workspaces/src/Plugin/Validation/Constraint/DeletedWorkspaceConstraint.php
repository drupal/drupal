<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Deleted workspace constraint.
 */
#[Constraint(
  id: 'DeletedWorkspace',
  label: new TranslatableMarkup('Deleted workspace', [], ['context' => 'Validation'])
)]
class DeletedWorkspaceConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'A workspace with this ID has been deleted but data still exists for it.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
