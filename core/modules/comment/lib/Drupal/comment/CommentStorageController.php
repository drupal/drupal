<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorageController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for comment entities.
 */
class CommentStorageController extends FieldableDatabaseStorageController implements CommentStorageControllerInterface {

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
  protected function postLoad(array &$queried_entities) {
    // Prepare standard comment fields.
    foreach ($queried_entities as &$record) {
      $record->name = $record->uid ? $record->registered_name : $record->name;
    }
    parent::postLoad($queried_entities);
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
    $count = $query->condition('c.entity_id', $comment->getCommentedEntityId())
      ->condition('c.entity_type', $comment->getCommentedEntityTypeId())
      ->condition('c.field_id', $comment->getFieldId())
      ->condition('c.status', CommentInterface::PUBLISHED)
      ->execute()
      ->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = $this->database->select('comment', 'c')
        ->fields('c', array('cid', 'name', 'changed', 'uid'))
        ->condition('c.entity_id', $comment->getCommentedEntityId())
        ->condition('c.entity_type', $comment->getCommentedEntityTypeId())
        ->condition('c.field_id', $comment->getFieldId())
        ->condition('c.status', CommentInterface::PUBLISHED)
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
        ->keys(array(
          'entity_id' => $comment->getCommentedEntityId(),
          'entity_type' => $comment->getCommentedEntityTypeId(),
          'field_id' => $comment->getFieldId(),
        ))
        ->execute();
    }
    else {
      // Comments do not exist.
      $entity = $comment->getCommentedEntity();
      // Get the user ID from the entity if it's set, or default to the
      // currently logged in user.
      if ($entity instanceof EntityOwnerInterface) {
        $last_comment_uid = $entity->getOwnerId();
      }
      if (!isset($last_comment_uid)) {
        // Default to current user when entity does not implement
        // EntityOwnerInterface or author is not set.
        $last_comment_uid = \Drupal::currentUser()->id();
      }
      $this->database->update('comment_entity_statistics')
        ->fields(array(
          'cid' => 0,
          'comment_count' => 0,
          // Use the created date of the entity if it's set, or default to
          // REQUEST_TIME.
          'last_comment_timestamp' => ($entity instanceof EntityChangedInterface) ? $entity->getChangedTime() : REQUEST_TIME,
          'last_comment_name' => '',
          'last_comment_uid' => $last_comment_uid,
        ))
        ->condition('entity_id', $comment->getCommentedEntityId())
        ->condition('entity_type', $comment->getCommentedEntityTypeId())
        ->condition('field_id', $comment->getFieldId())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->getCommentedEntityId())
      ->condition('field_id', $comment->getFieldId())
      ->condition('entity_type', $comment->getCommentedEntityTypeId());
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThreadPerThread(EntityInterface $comment) {
    $query = $this->database->select('comment', 'c')
      ->condition('entity_id', $comment->getCommentedEntityId())
      ->condition('field_id', $comment->getFieldId())
      ->condition('entity_type', $comment->getCommentedEntityTypeId())
      ->condition('thread', $comment->getParentComment()->getThread() . '.%', 'LIKE');
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
