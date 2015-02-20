<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraint.
 */

namespace Drupal\link\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Defines an access validation constraint for links.
 *
 * @Plugin(
 *   id = "LinkAccess",
 *   label = @Translation("Link URI can be accessed by the user.", context = "Validation"),
 * )
 */
class LinkAccessConstraint extends Constraint {

  public $message = "The path '@uri' is inaccessible.";

}
