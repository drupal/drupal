<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\CommentNameConstraint.
 */

namespace Drupal\comment\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Supports validating comment author names.
 *
 * @Constraint(
 *   id = "CommentName",
 *   label = @Translation("Comment author name", context = "Validation"),
 *   type = "entity:comment"
 * )
 */
class CommentNameConstraint extends CompositeConstraintBase {

  /**
   * Message shown when an anonymous user comments using a registered name.
   *
   * @var string
   */
  public $messageNameTaken = 'The name you used (%name) belongs to a registered user.';

  /**
   * Message shown when an admin changes the comment-author to an invalid user.
   *
   * @var string
   */
  public $messageRequired = 'You have to specify a valid author.';

  /**
   * Message shown when the name doesn't match the author's name.
   *
   * @var string
   */
  public $messageMatch = 'The specified author name does not match the comment author.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['name', 'uid'];
  }

}
