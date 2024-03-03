<?php

namespace Drupal\book\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for changing the book outline in pending revisions.
 */
#[Constraint(
  id: 'BookOutline',
  label: new TranslatableMarkup('Book outline.', [], ['context' => 'Validation'])
)]
class BookOutlineConstraint extends SymfonyConstraint {

  public $message = 'You can only change the book outline for the <em>published</em> version of this content.';

}
