<?php

namespace Drupal\datetime\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for DateTime items to ensure the format is correct.
 */
#[Constraint(
  id: 'DateTimeFormat',
  label: new TranslatableMarkup('Datetime format valid for datetime type.', [], ['context' => 'Validation'])
)]
class DateTimeFormatConstraint extends SymfonyConstraint {

  /**
   * Message for when the value isn't a string.
   *
   * @var string
   */
  public $badType = "The datetime value must be a string.";

  /**
   * Message for when the value isn't in the proper format.
   *
   * @var string
   */
  public $badFormat = "The datetime value '@value' is invalid for the format '@format'";

  /**
   * Message for when the value did not parse properly.
   *
   * @var string
   */
  public $badValue = "The datetime value '@value' did not parse properly for the format '@format'";

}
