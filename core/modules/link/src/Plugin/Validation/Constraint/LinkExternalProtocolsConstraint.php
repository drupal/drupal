<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Defines a protocol validation constraint for links to external URLs.
 */
#[Constraint(
  id: 'LinkExternalProtocols',
  label: new TranslatableMarkup('No dangerous external protocols', [], ['context' => 'Validation'])
)]
class LinkExternalProtocolsConstraint extends SymfonyConstraint {

  public $message = "The path '@uri' is invalid.";

}
