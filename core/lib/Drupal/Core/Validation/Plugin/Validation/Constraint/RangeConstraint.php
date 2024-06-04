<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Range constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @todo Move this below the TypedData core component.
 */
#[Constraint(
  id: 'Range',
  label: new TranslatableMarkup('Range', [], ['context' => 'Validation']),
  type: ['integer', 'float']
)]
class RangeConstraint extends Range {

  /**
   * {@inheritdoc}
   */
  public function __construct(...$args) {
    $this->notInRangeMessage = 'This value should be between %min and %max.';
    $this->minMessage = 'This value should be %limit or more.';
    $this->maxMessage = 'This value should be %limit or less.';
    parent::__construct(...$args);
  }

}
