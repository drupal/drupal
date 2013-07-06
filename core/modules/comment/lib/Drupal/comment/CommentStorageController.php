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
      'class' => '\Drupal\comment\CommentNewItem',
    );
    return $properties;
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
