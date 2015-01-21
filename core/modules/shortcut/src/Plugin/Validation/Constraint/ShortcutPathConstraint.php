<?php

/**
 * @file
 * Contains \Drupal\shortcut\Plugin\Validation\Constraint\ShortcutPathConstraint.
 */

namespace Drupal\shortcut\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates the path of a shortcut entry
 *
 * @Plugin(
 *   id = "ShortcutPath",
 *   label = @Translation("Shortcut path", context = "Validation"),
 *   type = { "entity" }
 * )
 */
class ShortcutPathConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The shortcut must correspond to a valid path on the site.';

}
