<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\Validation\Constraint\ForumLeafConstraint.
 */

namespace Drupal\forum\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Plugin(
 *   id = "ForumLeaf",
 *   label = @Translation("Forum leaf", context = "Validation"),
 * )
 */
class ForumLeafConstraint extends Constraint {

  public $selectForum = 'Select a forum.';
  public $noLeafMessage = 'The item %forum is a forum container, not a forum. Select one of the forums below instead.';
}
