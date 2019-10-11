<?php

namespace Drupal\Core\Path\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for valid system paths.
 *
 * @Constraint(
 *   id = "ValidPath",
 *   label = @Translation("Valid path.", context = "Validation"),
 * )
 */
class ValidPathConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = "Either the path '%link_path' is invalid or you do not have access to it.";

}
