<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\Validation\Constraint\TermParentConstraint.
 */

namespace Drupal\taxonomy\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Checks if a value is a valid taxonomy term parent (term id or 0).
 *
 * @Plugin(
 *   id = "TermParent",
 *   label = @Translation("Term parent", context = "Validation")
 * )
 */
class TermParentConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = '%id is not a valid parent for this term.';
}
