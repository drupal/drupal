<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Action\SaveComment.
 */

namespace Drupal\comment\Plugin\Action;

use Drupal\Core\Action\ActionBase;

/**
 * Saves a comment.
 *
 * @Action(
 *   id = "comment_save_action",
 *   label = @Translation("Save comment"),
 *   type = "comment"
 * )
 */
class SaveComment extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $comment->save();
  }

}
