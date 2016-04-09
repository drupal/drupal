<?php

namespace Drupal\comment\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\comment\CommentInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
