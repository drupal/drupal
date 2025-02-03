<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Supports validating widget constraints.
 */
#[Constraint(
  id: 'FieldWidgetConstraint',
  label: new TranslatableMarkup('Field widget constraint.')
)]
class FieldWidgetConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   */
  public string $message = 'Widget constraint has failed.';

}
