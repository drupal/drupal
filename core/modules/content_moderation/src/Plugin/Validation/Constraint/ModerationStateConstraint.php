<?php

namespace Drupal\content_moderation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Verifies that nodes have a valid moderation state.
 */
#[Constraint(
  id: 'ModerationState',
  label: new TranslatableMarkup('Valid moderation state', [], ['context' => 'Validation'])
)]
class ModerationStateConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'Invalid state transition from %from to %to',
    public $invalidStateMessage = 'State %state does not exist on %workflow workflow',
    public $invalidTransitionAccess = 'You do not have access to transition from %original_state to %new_state',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
