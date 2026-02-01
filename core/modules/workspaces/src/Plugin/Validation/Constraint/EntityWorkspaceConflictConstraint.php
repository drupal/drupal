<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for an entity being edited in multiple workspaces.
 */
#[Constraint(
  id: 'EntityWorkspaceConflict',
  label: new TranslatableMarkup('Entity workspace conflict', [], ['context' => 'Validation']),
  type: ['entity']
)]
class EntityWorkspaceConflictConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'The content is being edited in the @label workspace. As a result, your changes cannot be saved.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
