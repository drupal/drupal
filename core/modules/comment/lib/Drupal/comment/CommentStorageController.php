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
class CommentStorageController extends DatabaseStorageControllerNG {
  /**
   * The thread for which a lock was acquired.
   */
  protected $threadLock = '';

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildQuery().
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
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
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
   * Overrides Drupal\Core\Entity\DatabaseStorageControllerNG::create().
   */
  public function create(array $values) {
    if (empty($values['node_type']) && !empty($values['nid'])) {
      $node = node_load(is_object($values['nid']) ? $values['nid']->value : $values['nid']);
      $values['node_type'] = 'comment_node_' . $node->type;
    }
    return parent::create($values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   *
   * @see comment_int_to_alphadecimal()
   * @see comment_alphadecimal_to_int()
   */
  protected function preSave(EntityInterface $comment) {
    global $user;

    if (!isset($comment->status->value)) {
      $comment->status->value = user_access('skip comment approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED;
    }
    // Make sure we have a proper bundle name.
    if (!isset($comment->node_type->value)) {
      $comment->node_type->value = 'comment_node_' . $comment->nid->entity->type;
    }
    if ($comment->isNew()) {
      // Add the comment to database. This next section builds the thread field.
      // Also see the documentation for comment_view().
      if (!empty($comment->thread->value)) {
        // Allow calling code to set thread itself.
        $thread = $comment->thread->value;
      }
      else {
        if ($this->threadLock) {
          // As preSave() is protected, this can only happen when this class
          // is extended in a faulty manner.
          throw new LogicException('preSave is called again without calling postSave() or releaseThreadLock()');
        }
        if ($comment->pid->target_id == 0) {
          // This is a comment with no parent comment (depth 0): we start
          // by retrieving the maximum thread level.
          $max = db_query('SELECT MAX(thread) FROM {comment} WHERE nid = :nid', array(':nid' => $comment->nid->target_id))->fetchField();
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
          $parent = $comment->pid->entity;
          // Strip the "/" from the end of the parent thread.
          $parent->thread->value = (string) rtrim((string) $parent->thread->value, '/');
          $prefix = $parent->thread->value . '.';
          // Get the max value in *this* thread.
          $max = db_query("SELECT MAX(thread) FROM {comment} WHERE thread LIKE :thread AND nid = :nid", array(
            ':thread' => $parent->thread->value . '.%',
            ':nid' => $comment->nid->target_id,
          ))->fetchField();

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
            $parent_depth = count(explode('.', $parent->thread->value));
            $n = comment_alphadecimal_to_int($parts[$parent_depth]);
          }
        }
        // Finally, build the thread field for this new comment. To avoid
        // race conditions, get a lock on the thread. If aother process already
        // has the lock, just move to the next integer.
        do {
          $thread = $prefix . comment_int_to_alphadecimal(++$n) . '/';
        } while (!lock()->acquire("comment:{$comment->nid->target_id}:$thread"));
        $this->threadLock = $thread;
      }
      if (empty($comment->created->value)) {
        $comment->created->value = REQUEST_TIME;
      }
      if (empty($comment->changed->value)) {
        $comment->changed->value = $comment->created->value;
      }
      // We test the value with '===' because we need to modify anonymous
      // users as well.
      if ($comment->uid->target_id === $user->uid && $user->uid) {
        $comment->name->value = $user->name;
      }
      // Add the values which aren't passed into the function.
      $comment->thread->value = $thread;
      $comment->hostname->value = \Drupal::request()->getClientIP();
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $comment, $update) {
    $this->releaseThreadLock();
    // Update the {node_comment_statistics} table prior to executing the hook.
    $this->updateNodeStatistics($comment->nid->target_id);
    if ($comment->status->value == COMMENT_PUBLISHED) {
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
      $this->updateNodeStatistics($comment->nid->target_id);
    }
  }

  /**
   * Updates the comment statistics for a given node.
   *
   * The {node_comment_statistics} table has the following fields:
   * - last_comment_timestamp: The timestamp of the last comment for this node,
   *   or the node created timestamp if no comments exist for the node.
   * - last_comment_name: The name of the anonymous poster for the last comment.
   * - last_comment_uid: The user ID of the poster for the last comment for
   *   this node, or the node author's user ID if no comments exist for the
   *   node.
   * - comment_count: The total number of approved/published comments on this
   *   node.
   *
   * @param $nid
   *   The node ID.
   */
  protected function updateNodeStatistics($nid) {
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
   * Release the lock acquired for the thread in preSave().
   */
  protected function releaseThreadLock() {
    if ($this->threadLock) {
      lock()->release($this->threadLock);
      $this->threadLock = '';
    }
  }

  /**
   * Implements \Drupal\Core\Entity\DataBaseStorageControllerNG::basePropertyDefinitions().
   */
  public function baseFieldDefinitions() {
    $properties['cid'] = array(
      'label' => t('ID'),
      'description' => t('The comment ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The comment UUID.'),
      'type' => 'string_field',
    );
    $properties['pid'] = array(
      'label' => t('Parent ID'),
      'description' => t('The parent comment ID if this is a reply to a comment.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'comment'),
    );
    $properties['nid'] = array(
      'label' => t('Node ID'),
      'description' => t('The ID of the node of which this comment is a reply.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'node'),
      'required' => TRUE,
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The comment language code.'),
      'type' => 'language_field',
    );
    $properties['subject'] = array(
      'label' => t('Subject'),
      'description' => t('The comment title or subject.'),
      'type' => 'string_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the comment author.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t("The comment author's name."),
      'type' => 'string_field',
    );
    $properties['mail'] = array(
      'label' => t('e-mail'),
      'description' => t("The comment author's e-mail address."),
      'type' => 'string_field',
    );
    $properties['homepage'] = array(
      'label' => t('Homepage'),
      'description' => t("The comment author's home page address."),
      'type' => 'string_field',
    );
    $properties['hostname'] = array(
      'label' => t('Hostname'),
      'description' => t("The comment author's hostname."),
      'type' => 'string_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the comment was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the comment was last edited.'),
      'type' => 'integer_field',
    );
    $properties['status'] = array(
      'label' => t('Publishing status'),
      'description' => t('A boolean indicating whether the comment is published.'),
      'type' => 'boolean_field',
    );
    $properties['thread'] = array(
      'label' => t('Thread place'),
      'description' => t("The alphadecimal representation of the comment's place in a thread, consisting of a base 36 string prefixed by an integer indicating its length."),
      'type' => 'string_field',
    );
    $properties['node_type'] = array(
      // @todo: The bundle property should be stored so it's queryable.
      'label' => t('Node type'),
      'description' => t("The comment node type."),
      'type' => 'string_field',
      'queryable' => FALSE,
    );
    $properties['new'] = array(
      'label' => t('Comment new marker'),
      'description' => t("The comment 'new' marker for the current user (0 read, 1 new, 2 updated)."),
      'type' => 'integer_field',
      'computed' => TRUE,
      'class' => '\Drupal\comment\FieldNewItem',
    );
    return $properties;
  }
}
