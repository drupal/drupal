<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorage.
 */

namespace Drupal\comment;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\ContentEntityDatabaseStorage class,
 * adding required special handling for comment entities.
 */
class CommentStorage extends ContentEntityDatabaseStorage implements CommentStorageInterface {

  /**
   * The comment statistics service.
   *
   * @var \Drupal\comment\CommentStatisticsInterface
   */
  protected $statistics;

  /**
   * Constructs a CommentStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\comment\CommentStatisticsInterface $comment_statistics
   *   The comment statistics service.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, FieldInfo $field_info, CommentStatisticsInterface $comment_statistics) {
    parent::__construct($entity_info, $database, $field_info);
    $this->statistics = $comment_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('field.info'),
      $container->get('comment.statistics')
    );
  }


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
    $this->statistics->update($comment);
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
