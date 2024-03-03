<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Defines an access validation constraint for links.
 */
#[Constraint(
  id: 'LinkAccess',
  label: new TranslatableMarkup('Link URI can be accessed by the user.', [], ['context' => 'Validation'])
)]
class LinkAccessConstraint extends SymfonyConstraint {

  public $message = "The path '@uri' is inaccessible.";

}
