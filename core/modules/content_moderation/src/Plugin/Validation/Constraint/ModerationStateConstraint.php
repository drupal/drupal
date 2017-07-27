<?php

namespace Drupal\content_moderation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Verifies that nodes have a valid moderation state.
 *
 * @Constraint(
 *   id = "ModerationState",
 *   label = @Translation("Valid moderation state", context = "Validation")
 * )
 */
class ModerationStateConstraint extends Constraint {

  public $message = 'Invalid state transition from %from to %to';
  public $invalidStateMessage = 'State %state does not exist on %workflow workflow';

}
