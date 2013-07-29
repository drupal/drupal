<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Validation\Constraint\NodeChangedConstraint.
 */

namespace Drupal\node\Plugin\Validation\Constraint;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the node changed timestamp.
 *
 * @Plugin(
 *   id = "NodeChanged",
 *   label = @Translation("Node changed", context = "Validation")
 * )
 */
class NodeChangedConstraint extends Constraint {

  public $message = 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.';
}
