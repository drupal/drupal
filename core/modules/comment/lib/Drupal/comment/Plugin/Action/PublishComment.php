<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Action\PublishComment.
 */

namespace Drupal\comment\Plugin\Action;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Action\ActionBase;
use Drupal\comment\CommentInterface;

/**
 * Publishes a comment.
 *
 * @Action(
 *   id = "comment_publish_action",
 *   label = @Translation("Publish comment"),
 *   type = "comment"
 * )
 */
class PublishComment extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($comment = NULL) {
    $comment->status->value = CommentInterface::PUBLISHED;
    $comment->save();
  }

}
