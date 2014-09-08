<?php

/**
 * @file
 * Contains \Drupal\comment\CommentStorageSchema.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the comment schema handler.
 */
class CommentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['comment_field_data']['fields']['created']['not null'] = TRUE;
    $schema['comment_field_data']['fields']['thread']['not null'] = TRUE;

    unset($schema['comment_field_data']['indexes']['comment_field__pid__target_id']);
    unset($schema['comment_field_data']['indexes']['comment_field__entity_id__target_id']);
    $schema['comment_field_data']['indexes'] += array(
      'comment__status_pid' => array('pid', 'status'),
      'comment__num_new' => array(
        'entity_id',
        'entity_type',
        'comment_type',
        'status',
        'created',
        'cid',
        'thread',
      ),
      'comment__entity_langcode' => array(
        'entity_id',
        'entity_type',
        'comment_type',
        'default_langcode',
      ),
      'comment__created' => array('created'),
    );
    $schema['comment_field_data']['foreign keys'] += array(
      'comment__author' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    );

    return $schema;
  }

}
