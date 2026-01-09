<?php

declare(strict_types=1);

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for link title subfields if a URL was entered.
 */
#[Constraint(
  id: 'LinkTitleRequired',
  label: new TranslatableMarkup('A link title was entered if a URL was entered.', [], ['context' => 'Validation'])
)]
class LinkTitleRequiredConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public $message = "The Link text field is required if there is URL input.";

}
