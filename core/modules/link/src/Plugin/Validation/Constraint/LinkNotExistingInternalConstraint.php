<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Defines a protocol validation constraint for links to broken internal URLs.
 */
#[Constraint(
  id: 'LinkNotExistingInternal',
  label: new TranslatableMarkup('No broken internal links', [], ['context' => 'Validation'])
)]
class LinkNotExistingInternalConstraint extends SymfonyConstraint {

  public $message = "The path '@uri' is invalid.";

}
