<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorageController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Component\Uuid\Uuid;
use LogicException;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for comment entities.
 */
class CommentStorageController extends DatabaseStorageControllerNG implements CommentStorageControllerInterface {

  /**
   * The thread for which a lock was acquired.
   */
  protected $threadLock = '';

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $revision_id);
    // Specify additional fields from the user and node tables.
    $query->innerJoin('node', 'n', 'base.nid = n.nid');
    $query->addField('n', 'type', 'node_type');
    $query->innerJoin('users', 'u', 'base.uid = u.uid');
    // @todo: Move to a computed 'name' field instead.
    $query->addField('u', 'name', 'registered_name');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function attachLoad(&$records, $load_revision = FALSE) {
    // Prepare standard comment fields.
    foreach ($records as $key => $record) {
      $record->name = $record->uid ? $record->registered_name : $record->name;
      $record->node_type = 'comment_node_' . $record->node_type;
      $records[$key] = $record;
    }
    parent::attachLoad($records, $load_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function updateNodeStatistics($nid) {
    // Allow bulk updates and inserts to temporarily disable the
    // maintenance of the {node_comment_statistics} table.
    if (!variable_get('comment_maintain_node_statistics', TRUE)) {
      return;
    }

    $count = db_query('SELECT COUNT(cid) FROM {comment} WHERE nid = :nid AND status = :status', array(
      ':nid' => $nid,
      ':status' => COMMENT_PUBLISHED,
    ))->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = db_query_range('SELECT cid, name, changed, uid FROM {comment} WHERE nid = :nid AND status = :status ORDER BY cid DESC', 0, 1, array(
        ':nid' => $nid,
        ':status' => COMMENT_PUBLISHED,
      ))->fetchObject();
      db_update('node_comment_statistics')
        ->fields(array(
          'cid' => $last_reply->cid,
          'comment_count' => $count,
          'last_comment_timestamp' => $last_reply->changed,
          'last_comment_name' => $last_reply->uid ? '' : $last_reply->name,
          'last_comment_uid' => $last_reply->uid,
        ))
        ->condition('nid', $nid)
        ->execute();
    }
    else {
      // Comments do not exist.
      $node = db_query('SELECT uid, created FROM {node_field_data} WHERE nid = :nid LIMIT 1', array(':nid' => $nid))->fetchObject();
      db_update('node_comment_statistics')
        ->fields(array(
          'cid' => 0,
          'comment_count' => 0,
          'last_comment_timestamp' => $node->created,
          'last_comment_name' => '',
          'last_comment_uid' => $node->uid,
        ))
        ->condition('nid', $nid)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThread(EntityInterface $comment) {
    return db_query('SELECT MAX(thread) FROM {comment} WHERE nid = :nid', array(':nid' => $comment->nid->target_id))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThreadPerThread(EntityInterface $comment) {
    return $this->database->query("SELECT MAX(thread) FROM {comment} WHERE thread LIKE :thread AND nid = :nid", array(
      ':thread' => rtrim($comment->pid->entity->thread->value, '/') . '.%',
      ':nid' => $comment->nid->target_id,
    ))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCids(array $comments) {
    return $this->database->select('comment', 'c')
      ->fields('c', array('cid'))
      ->condition('pid', array_keys($comments))
      ->execute()
      ->fetchCol();
  }
}
