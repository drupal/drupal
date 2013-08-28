<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorageController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\comment\CommentInterface;

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
    // Specify additional fields from the user table.
    $query->innerJoin('users', 'u', 'base.uid = u.uid');
    // @todo: Move to a computed 'name' field instead.
    $query->addField('u', 'name', 'registered_name');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function attachLoad(&$records, $load_revision = FALSE) {
    // Set up standard comment properties.
    foreach ($records as $key => &$record) {
      $record->name = $record->uid ? $record->registered_name : $record->name;
    }
    parent::attachLoad($records, $load_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityStatistics(CommentInterface $comment) {
    global $user;
    // Allow bulk updates and inserts to temporarily disable the
    // maintenance of the {comment_entity_statistics} table.
    if (!\Drupal::state()->get('comment.maintain_entity_statistics')) {
      return;
    }

    $query = $this->database->select('comment', 'c');
    $query->addExpression('COUNT(cid)');
    $count = $query->condition('c.entity_id', $comment->entity_id->value)
      ->condition('c.entity_type', $comment->entity_type->value)
      ->condition('c.field_name', $comment->field_name->value)
      ->condition('c.status', COMMENT_PUBLISHED)
      ->execute()
      ->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = $this->database->select('comment', 'c')
        ->fields('c', array('cid', 'name', 'changed', 'uid'))
        ->condition('c.entity_id', $comment->entity_id->value)
        ->condition('c.entity_type', $comment->entity_type->value)
        ->condition('c.field_name', $comment->field_name->value)
        ->condition('c.status', COMMENT_PUBLISHED)
        ->orderBy('c.created', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchObject();
      $this->database->update('comment_entity_statistics')
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
      $this->database->update('comment_entity_statistics')
        ->fields(array(
          'cid' => 0,
          'comment_count' => 0,
          // Use the created date of the entity if it's set,
          // or default to REQUEST_TIME.
          'last_comment_timestamp' => isset($entity->created->value) ? $entity->created->value : REQUEST_TIME,
          'last_comment_name' => '',
          // @todo Refactor when http://drupal.org/node/585838 lands.
          // Get the user ID from the entity if it's set, or default to the
          // currently logged in user.
          'last_comment_uid' => isset($entity->uid->target_id) ? $entity->uid->target_id : $user->id(),
        ))
        ->condition('entity_id', $comment->entity_id->value)
        ->condition('entity_type', $comment->entity_type->value)
        ->condition('field_name', $comment->field_name->value)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->entity_id->value)
      ->condition('field_name', $comment->field_name->value)
      ->condition('entity_type', $comment->entity_type->value);
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThreadPerThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->entity_id->value)
      ->condition('field_name', $comment->field_name->value)
      ->condition('entity_type', $comment->entity_type->value)
      ->condition('thread', $comment->pid->entity->thread->value . '.%', 'LIKE');
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
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
