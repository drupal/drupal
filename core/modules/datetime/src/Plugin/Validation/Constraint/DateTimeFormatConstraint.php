<?php

namespace Drupal\datetime\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for DateTime items to ensure the format is correct.
 */
#[Constraint(
  id: 'DateTimeFormat',
  label: new TranslatableMarkup('Datetime format valid for datetime type.', [], ['context' => 'Validation'])
)]
class DateTimeFormatConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $badType = "The datetime value must be a string.",
    public $badFormat = "The datetime value '@value' is invalid for the format '@format'",
    public $badValue = "The datetime value '@value' did not parse properly for the format '@format'",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
