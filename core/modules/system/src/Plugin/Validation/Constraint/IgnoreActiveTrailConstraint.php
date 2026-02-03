<?php

declare(strict_types=1);

namespace Drupal\system\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Constraint on the ignore_active_trail configuration in system menu blocks.
 */
#[Constraint(
  id: 'IgnoreActiveTrail',
  label: new TranslatableMarkup('Whether the ignore_active_trail setting is valid', [], ['context' => 'Validation'])
)]
class IgnoreActiveTrailConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public string $message = 'The "ignore_active_trail" setting on a system menu block cannot be enabled if "level" is greater than 1 or "expand_all_items" is not enabled and "depth" is greater than 1.';

}
