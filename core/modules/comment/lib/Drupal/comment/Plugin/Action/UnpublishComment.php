<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Action\UnpublishComment.
 */

namespace Drupal\comment\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ActionBase;
use Drupal\comment\CommentInterface;

/**
 * Unpublishes a comment.
 *
 * @Action(
 *   id = "comment_unpublish_action",
 *   label = @Translation("Unpublish comment"),
 *   type = "comment"
 * )
 */
class UnpublishComment extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $comment->status->value = CommentInterface::NOT_PUBLISHED;
    $comment->save();
  }

}
