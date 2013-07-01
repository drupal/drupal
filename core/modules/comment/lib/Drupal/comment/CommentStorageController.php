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
          // Use created date of entity or default to REQUEST_TIME if none
          // exists.
          'last_comment_timestamp' => isset($entity->created->value) ? $entity->created->value : REQUEST_TIME,
          'last_comment_name' => '',
          // @todo Refactor when http://drupal.org/node/585838 lands.
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
   * {@inheritdoc}
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
      'type' => 'uuid_field',
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
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t("The comment author's name."),
      'type' => 'string_field',
      'settings' => array('default_value' => ''),
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
