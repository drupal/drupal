<?php

namespace Drupal\book\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for changing the book outline in pending revisions.
 *
 * @Constraint(
 *   id = "BookOutline",
 *   label = @Translation("Book outline.", context = "Validation"),
 * )
 */
class BookOutlineConstraint extends Constraint {

  public $message = 'You can only change the book outline for the <em>published</em> version of this content.';

}
