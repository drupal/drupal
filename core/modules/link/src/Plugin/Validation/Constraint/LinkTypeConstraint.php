<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for links receiving data allowed by its settings.
 *
 * @Constraint(
 *   id = "LinkType",
 *   label = @Translation("Link data valid for link type.", context = "Validation"),
 * )
 */
class LinkTypeConstraint extends Constraint {

  public $message = "The path '@uri' is invalid.";

}
