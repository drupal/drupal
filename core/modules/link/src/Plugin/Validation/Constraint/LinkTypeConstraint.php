<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for links receiving data allowed by its settings.
 */
#[Constraint(
  id: 'LinkType',
  label: new TranslatableMarkup('Link data valid for link type.', [], ['context' => 'Validation'])
)]
class LinkTypeConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public $message = "The path '@uri' is invalid.";

}
