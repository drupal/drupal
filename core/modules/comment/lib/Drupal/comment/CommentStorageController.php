<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorageController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageController;
use LogicException;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for comment entities.
 */
class CommentStorageController extends DatabaseStorageController {
  /**
   * The thread for which a lock was acquired.
   */
  protected $threadLock = '';


  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $revision_id);
    // Specify additional fields from the user table.
    $query->innerJoin('users', 'u', 'base.uid = u.uid');
    $query->addField('u', 'name', 'registered_name');
    $query->fields('u', array('uid', 'signature', 'signature_format'));
    return $query;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$comments, $load_revision = FALSE) {
    // Set up standard comment properties.
    foreach ($comments as $key => $comment) {
      $comment->name = $comment->uid ? $comment->registered_name : $comment->name;
      $comment->new = comment_mark($comment);
      $comments[$key] = $comment;
    }
    parent::attachLoad($comments, $load_revision);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   *
   * @see comment_int_to_alphadecimal()
   * @see comment_alphadecimal_to_int()
   */
  protected function preSave(EntityInterface $comment) {
    global $user;

    if (!isset($comment->status)) {
      $comment->status = user_access('skip comment approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED;
    }
    if (!$comment->cid) {
      // Add the comment to database. This next section builds the thread field.
      // Also see the documentation for comment_view().
      if (!empty($comment->thread)) {
        // Allow calling code to set thread itself.
        $thread = $comment->thread;
      }
      else {
        if ($this->threadLock) {
          // As preSave() is protected, this can only happen when this class
          // is extended in a faulty manner.
          throw new LogicException('preSave is called again without calling postSave() or releaseThreadLock()');
        }
        if ($comment->pid == 0) {
          // This is a comment with no parent comment (depth 0): we start
          // by retrieving the maximum thread level.
          $query = db_select('comment', 'c')
            ->condition('entity_id', $comment->entity_id)
            ->condition('field_name', $comment->field_name)
            ->condition('entity_type', $comment->entity_type);
          $query->addExpression('MAX(thread)', 'thread');
          $max = $query->execute()
            ->fetchField();
          // Strip the "/" from the end of the thread.
          $max = rtrim($max, '/');
          // We need to get the value at the correct depth.
          $parts = explode('.', $max);
          $n = comment_alphadecimal_to_int($parts[0]);
          $prefix = '';
        }
        else {
          // This is a comment with a parent comment, so increase the part of
          // the thread value at the proper depth.
          // Get the parent comment:
          $parent = comment_load($comment->pid);
          // Strip the "/" from the end of the parent thread.
          $parent->thread = (string) rtrim((string) $parent->thread, '/');
          $prefix = $parent->thread . '.';
          // Get the max value in *this* thread.
          $query = db_select('comment', 'c')
            ->condition('entity_id', $comment->entity_id)
            ->condition('field_name', $comment->field_name)
            ->condition('entity_type', $comment->entity_type)
            ->condition('thread', $parent->thread . '.%', 'LIKE');
          $query->addExpression('MAX(thread)', 'thread');
          $max = $query->execute()
            ->fetchField();

          if ($max == '') {
            // First child of this parent. As the other two cases do an
            // increment of the thread number before creating the thread
            // string set this to -1 so it requires an increment too.
            $n = -1;
          }
          else {
            // Strip the "/" at the end of the thread.
            $max = rtrim($max, '/');
            // Get the value at the correct depth.
            $parts = explode('.', $max);
            $parent_depth = count(explode('.', $parent->thread));
            $n = comment_alphadecimal_to_int($parts[$parent_depth]);
          }
        }
        // Finally, build the thread field for this new comment. To avoid
        // race conditions, get a lock on the thread. If aother process already
        // has the lock, just move to the next integer.
        do {
          $thread = $prefix . comment_int_to_alphadecimal(++$n) . '/';
        } while (!lock()->acquire("comment:$comment->entity_id:$comment->entity_type:$thread"));
        $this->threadLock = $thread;
      }
      if (empty($comment->created)) {
        $comment->created = REQUEST_TIME;
      }
      if (empty($comment->changed)) {
        $comment->changed = $comment->created;
      }
      // We test the value with '===' because we need to modify anonymous
      // users as well.
      if ($comment->uid === $user->uid && isset($user->name)) {
        $comment->name = $user->name;
      }
      // Add the values which aren't passed into the function.
      $comment->thread = $thread;
      $comment->hostname = ip_address();
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $comment, $update) {
    $this->releaseThreadLock();
    // Update the {comment_entity_statistics} table prior to executing the hook.
    $this->updateEntityStatistics($comment);
    if ($comment->status == COMMENT_PUBLISHED) {
      module_invoke_all('comment_publish', $comment);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($comments) {
    // Delete the comments' replies.
    $query = db_select('comment', 'c')
      ->fields('c', array('cid'))
      ->condition('pid', array(array_keys($comments)), 'IN');
    $child_cids = $query->execute()->fetchCol();
    comment_delete_multiple($child_cids);

    foreach ($comments as $comment) {
      $this->updateEntityStatistics($comment);
    }
  }

  /**
   * Updates the comment statistics for a given entity.
   *
   * The {comment_entity_statistics} table has the following fields:
   * - last_comment_timestamp: The timestamp of the last comment for this entity,
   *   or the entity created timestamp if no comments exist for the entity.
   * - last_comment_name: The name of the anonymous poster for the last comment.
   * - last_comment_uid: The user ID of the poster for the last comment for
   *   this entity, or the entity author's user ID if no comments exist for the
   *   entity.
   * - comment_count: The total number of approved/published comments on this
   *   entity.
   *
   * @param Drupal\comment\Comment $comment
   *   The comment being saved.
   */
  protected function updateEntityStatistics($comment) {
    global $user;
    // Allow bulk updates and inserts to temporarily disable the
    // maintenance of the {comment_entity_statistics} table.
    if (!state()->get('comment.maintain_entity_statistics', TRUE)) {
      return;
    }

    $query = db_select('comment', 'c');
    $query->addExpression('COUNT(cid)');
    $count = $query->condition('c.entity_id', $comment->entity_id)
    ->condition('c.entity_type', $comment->entity_type)
    ->condition('c.field_name', $comment->field_name)
    ->condition('c.status', COMMENT_PUBLISHED)
    ->execute()
    ->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = db_select('comment', 'c')
      ->fields('c', array('cid', 'name', 'changed', 'uid'))
      ->condition('c.entity_id', $comment->entity_id)
      ->condition('c.entity_type', $comment->entity_type)
      ->condition('c.field_name', $comment->field_name)
      ->condition('c.status', COMMENT_PUBLISHED)
      ->orderBy('c.created', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();
      db_update('comment_entity_statistics')
        ->fields(array(
          'cid' => $last_reply->cid,
          'comment_count' => $count,
          'last_comment_timestamp' => $last_reply->changed,
          'last_comment_name' => $last_reply->uid ? '' : $last_reply->name,
          'last_comment_uid' => $last_reply->uid,
        ))
        ->condition('entity_id', $comment->entity_id)
        ->condition('entity_type', $comment->entity_type)
        ->condition('field_name', $comment->field_name)
        ->execute();
    }
    else {
      // Comments do not exist.
      $entity = entity_load($comment->entity_type, $comment->entity_id);
      db_update('comment_entity_statistics')
        ->fields(array(
          'cid' => 0,
          'comment_count' => 0,
          // Use created date of entity or default to REQUEST_TIME if none
          // exists.
          'last_comment_timestamp' => isset($entity->created) ? $entity->created : REQUEST_TIME,
          'last_comment_name' => '',
          // @todo refactor when http://drupal.org/node/585838 lands.
          // Get uid from entity or default to logged in user if none exists.
          'last_comment_uid' => isset($entity->uid) ? $entity->uid : $user->uid,
        ))
        ->condition('entity_id', $comment->entity_id)
        ->condition('entity_type', $comment->entity_type)
        ->condition('field_name', $comment->field_name)
        ->execute();
    }
  }

  /**
   * Release the lock acquired for the thread in preSave().
   */
  protected function releaseThreadLock() {
    if ($this->threadLock) {
      lock()->release($this->threadLock);
      $this->threadLock = '';
    }
  }
}
