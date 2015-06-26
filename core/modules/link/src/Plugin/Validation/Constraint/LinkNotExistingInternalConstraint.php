<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Validation\Constraint\LinkNotExistingInternalConstraint.
 */

namespace Drupal\link\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a protocol validation constraint for links to broken internal URLs.
 *
 * @Constraint(
 *   id = "LinkNotExistingInternal",
 *   label = @Translation("No broken internal links", context = "Validation"),
 * )
 */
class LinkNotExistingInternalConstraint extends Constraint {

  public $message = "The path '@uri' is invalid.";

}
