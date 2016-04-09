<?php

namespace Drupal\comment\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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
    $comment->setPublished(TRUE);
    $comment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\comment\CommentInterface $object */
    $result = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
