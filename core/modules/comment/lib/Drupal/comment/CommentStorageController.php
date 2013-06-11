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
    // Specify additional fields from the user table.
    $query->innerJoin('users', 'u', 'base.uid = u.uid');
    // @todo: Move to a computed 'name' field instead.
    $query->addField('u', 'name', 'registered_name');
    return $query;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$records, $load_revision = FALSE) {
    // Set up standard comment properties.
    foreach ($records as $key => &$record) {
      $record->name = $record->uid ? $record->registered_name : $record->name;
    }
    parent::attachLoad($records, $load_revision);
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
          $query = db_select('comment', 'c')
            ->condition('entity_id', $comment->entity_id->value)
            ->condition('field_name', $comment->field_name->value)
            ->condition('entity_type', $comment->entity_type->value);
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
          $parent = $comment->pid->entity;
          // Strip the "/" from the end of the parent thread.
          $parent->thread->value = (string) rtrim((string) $parent->thread->value, '/');
          $prefix = $parent->thread->value . '.';
          // Get the max value in *this* thread.
          $query = db_select('comment', 'c')
            ->condition('entity_id', $comment->entity_id->value)
            ->condition('field_name', $comment->field_name->value)
            ->condition('entity_type', $comment->entity_type->value)
            ->condition('thread', $parent->thread->value . '.%', 'LIKE');
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
            $parent_depth = count(explode('.', $parent->thread->value));
            $n = comment_alphadecimal_to_int($parts[$parent_depth]);
          }
        }
        // Finally, build the thread field for this new comment. To avoid
        // race conditions, get a lock on the thread. If aother process already
        // has the lock, just move to the next integer.
        do {
          $thread = $prefix . comment_int_to_alphadecimal(++$n) . '/';
        } while (!lock()->acquire("comment:{$comment->entity_id->value}:{$comment->entity_type->value}:$thread"));
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
   * {@inheritdoc}
   */
  protected function postSave(EntityInterface $comment, $update) {
    $this->releaseThreadLock();
    // Update the {comment_entity_statistics} table prior to executing the hook.
    $this->updateEntityStatistics($comment);
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
   * @param \Drupal\comment\Plugin\Core\Entity\Comment $comment
   *   The comment being saved.
   */
  protected function updateEntityStatistics($comment) {
    global $user;
    // Allow bulk updates and inserts to temporarily disable the
    // maintenance of the {comment_entity_statistics} table.
    if (!\Drupal::state()->get('comment.maintain_entity_statistics')) {
      return;
    }

    $query = db_select('comment', 'c');
    $query->addExpression('COUNT(cid)');
    $count = $query->condition('c.entity_id', $comment->entity_id->value)
      ->condition('c.entity_type', $comment->entity_type->value)
      ->condition('c.field_name', $comment->field_name->value)
      ->condition('c.status', COMMENT_PUBLISHED)
      ->execute()
      ->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = db_select('comment', 'c')
        ->fields('c', array('cid', 'name', 'changed', 'uid'))
        ->condition('c.entity_id', $comment->entity_id->value)
        ->condition('c.entity_type', $comment->entity_type->value)
        ->condition('c.field_name', $comment->field_name->value)
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
        ->condition('entity_id', $comment->entity_id->value)
        ->condition('entity_type', $comment->entity_type->value)
        ->condition('field_name', $comment->field_name->value)
        ->execute();
    }
    else {
      // Comments do not exist.
      $entity = entity_load($comment->entity_type->value, $comment->entity_id->value);
      db_update('comment_entity_statistics')
        ->fields(array(
          'cid' => 0,
          'comment_count' => 0,
          // Use created date of entity or default to REQUEST_TIME if none
          // exists.
          'last_comment_timestamp' => isset($entity->created->value) ? $entity->created->value : REQUEST_TIME,
          'last_comment_name' => '',
          // @todo refactor when http://drupal.org/node/585838 lands.
          // Get uid from entity or default to logged in user if none exists.
          'last_comment_uid' => isset($entity->uid->target_id) ? $entity->uid->target_id : $user->uid,
        ))
        ->condition('entity_id', $comment->entity_id->value)
        ->condition('entity_type', $comment->entity_type->value)
        ->condition('field_name', $comment->field_name->value)
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
    $properties['entity_id'] = array(
      'label' => t('Entity ID'),
      'description' => t('The ID of the entity of which this comment is a reply.'),
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
    $properties['entity_type'] = array(
      'label' => t('Entity type'),
      'description' => t("The entity type to which this comment is attached."),
      'type' => 'string_field',
    );
    $properties['field_name'] = array(
      'label' => t('Field name'),
      'description' => t("The comment field name."),
      'type' => 'string_field',
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
