<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorageController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for comment entities.
 */
class CommentStorageController extends FieldableDatabaseStorageController implements CommentStorageControllerInterface {

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
    // Prepare standard comment fields.
    foreach ($records as &$record) {
      $record->name = $record->uid ? $record->registered_name : $record->name;
    }
    parent::attachLoad($records, $load_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityStatistics(CommentInterface $comment) {
    // Allow bulk updates and inserts to temporarily disable the maintenance of
    // the {comment_entity_statistics} table.
    if (!\Drupal::state()->get('comment.maintain_entity_statistics')) {
      return;
    }

    $query = $this->database->select('comment', 'c');
    $query->addExpression('COUNT(cid)');
    $count = $query->condition('c.entity_id', $comment->entity_id->value)
      ->condition('c.entity_type', $comment->entity_type->value)
      ->condition('c.field_id', $comment->field_id->value)
      ->condition('c.status', COMMENT_PUBLISHED)
      ->execute()
      ->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = $this->database->select('comment', 'c')
        ->fields('c', array('cid', 'name', 'changed', 'uid'))
        ->condition('c.entity_id', $comment->entity_id->value)
        ->condition('c.entity_type', $comment->entity_type->value)
        ->condition('c.field_id', $comment->field_id->value)
        ->condition('c.status', COMMENT_PUBLISHED)
        ->orderBy('c.created', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchObject();
      // Use merge here because entity could be created before comment field.
      $this->database->merge('comment_entity_statistics')
        ->fields(array(
          'cid' => $last_reply->cid,
          'comment_count' => $count,
          'last_comment_timestamp' => $last_reply->changed,
          'last_comment_name' => $last_reply->uid ? '' : $last_reply->name,
          'last_comment_uid' => $last_reply->uid,
        ))
        ->key(array(
          'entity_id' => $comment->entity_id->value,
          'entity_type' => $comment->entity_type->value,
          'field_id' => $comment->field_id->value,
        ))
        ->execute();
    }
    else {
      // Comments do not exist.
      $entity = entity_load($comment->entity_type->value, $comment->entity_id->value);
      $this->database->update('comment_entity_statistics')
        ->fields(array(
          'cid' => 0,
          'comment_count' => 0,
          // Use the created date of the entity if it's set, or default to
          // REQUEST_TIME.
          'last_comment_timestamp' => ($entity instanceof EntityChangedInterface) ? $entity->getChangedTime() : REQUEST_TIME,
          'last_comment_name' => '',
          // @todo Use $entity->getAuthorId() after https://drupal.org/node/2078387
          // Get the user ID from the entity if it's set, or default to the
          // currently logged in user.
          'last_comment_uid' => $entity->getPropertyDefinition('uid') ? $entity->get('uid')->value : \Drupal::currentUser()->id(),
        ))
        ->condition('entity_id', $comment->entity_id->value)
        ->condition('entity_type', $comment->entity_type->value)
        ->condition('field_id', $comment->field_id->value)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->entity_id->value)
      ->condition('field_id', $comment->field_id->value)
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
      ->condition('field_id', $comment->field_id->value)
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
