<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorage.
 */

namespace Drupal\comment;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Session\AccountInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a CommentStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\comment\CommentStatisticsInterface $comment_statistics
   *   The comment statistics service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, CommentStatisticsInterface $comment_statistics, AccountInterface $current_user) {
    parent::__construct($entity_info, $database, $entity_manager);
    $this->statistics = $comment_statistics;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('comment.statistics'),
      $container->get('current_user')
    );
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
  public function getMaxThread(CommentInterface $comment) {
    $query = $this->database->select('comment_field_data', 'c')
      ->condition('entity_id', $comment->getCommentedEntityId())
      ->condition('field_name', $comment->getFieldName())
      ->condition('entity_type', $comment->getCommentedEntityTypeId())
      ->condition('default_langcode', 1);
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxThreadPerThread(CommentInterface $comment) {
    $query = $this->database->select('comment_field_data', 'c')
      ->condition('entity_id', $comment->getCommentedEntityId())
      ->condition('field_name', $comment->getFieldName())
      ->condition('entity_type', $comment->getCommentedEntityTypeId())
      ->condition('thread', $comment->getParentComment()->getThread() . '.%', 'LIKE')
      ->condition('default_langcode', 1);
    $query->addExpression('MAX(thread)', 'thread');
    return $query->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOrdinal(CommentInterface $comment, $comment_mode, $divisor = 1) {
    // Count how many comments (c1) are before $comment (c2) in display order.
    // This is the 0-based display ordinal.
    $query = $this->database->select('comment_field_data', 'c1');
    $query->innerJoin('comment_field_data', 'c2', 'c2.entity_id = c1.entity_id AND c2.entity_type = c1.entity_type AND c2.field_name = c1.field_name');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('c2.cid', $comment->id());
    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('c1.status', CommentInterface::PUBLISHED);
    }

    if ($comment_mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      // For rendering flat comments, cid is used for ordering comments due to
      // unpredictable behavior with timestamp, so we make the same assumption
      // here.
      $query->condition('c1.cid', $comment->id(), '<');
    }
    else {
      // For threaded comments, the c.thread column is used for ordering. We can
      // use the sorting code for comparison, but must remove the trailing
      // slash.
      $query->where('SUBSTRING(c1.thread, 1, (LENGTH(c1.thread) - 1)) < SUBSTRING(c2.thread, 1, (LENGTH(c2.thread) - 1))');
    }

    $query->condition('c1.default_langcode', 1);
    $query->condition('c2.default_langcode', 1);

    $ordinal = $query->execute()->fetchField();

    return ($divisor > 1) ? floor($ordinal / $divisor) : $ordinal;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewCommentPageNumber($total_comments, $new_comments, ContentEntityInterface $entity, $field_name = 'comment') {
    $instance = $entity->getFieldDefinition($field_name);
    $comments_per_page = $instance->getSetting('per_page');

    if ($total_comments <= $comments_per_page) {
      // Only one page of comments.
      $count = 0;
    }
    elseif ($instance->getSetting('default_mode') == CommentManagerInterface::COMMENT_MODE_FLAT) {
      // Flat comments.
      $count = $total_comments - $new_comments;
    }
    else {
      // Threaded comments.

      // 1. Find all the threads with a new comment.
      $unread_threads_query = $this->database->select('comment_field_data', 'comment')
        ->fields('comment', array('thread'))
        ->condition('entity_id', $entity->id())
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('field_name', $field_name)
        ->condition('status', CommentInterface::PUBLISHED)
        ->condition('default_langcode', 1)
        ->orderBy('created', 'DESC')
        ->orderBy('cid', 'DESC')
        ->range(0, $new_comments);

      // 2. Find the first thread.
      $first_thread_query = $this->database->select($unread_threads_query, 'thread');
      $first_thread_query->addExpression('SUBSTRING(thread, 1, (LENGTH(thread) - 1))', 'torder');
      $first_thread = $first_thread_query
        ->fields('thread', array('thread'))
        ->orderBy('torder')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      // Remove the final '/'.
      $first_thread = substr($first_thread, 0, -1);

      // Find the number of the first comment of the first unread thread.
      $count = $this->database->query('SELECT COUNT(*) FROM {comment_field_data} WHERE entity_id = :entity_id
                        AND entity_type = :entity_type
                        AND field_name = :field_name
                        AND status = :status
                        AND SUBSTRING(thread, 1, (LENGTH(thread) - 1)) < :thread
                        AND default_langcode = 1', array(
        ':status' => CommentInterface::PUBLISHED,
        ':entity_id' => $entity->id(),
        ':field_name' => $field_name,
        ':entity_type' => $entity->getEntityTypeId(),
        ':thread' => $first_thread,
      ))->fetchField();
    }

    return $comments_per_page > 0 ? (int) ($count / $comments_per_page) : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCids(array $comments) {
    return $this->database->select('comment_field_data', 'c')
      ->fields('c', array('cid'))
      ->condition('pid', array_keys($comments))
      ->condition('default_langcode', 1)
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

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

  /**
   * {@inheritdoc}
   */
  public function getUnapprovedCount() {
    return  $this->database->select('comment_field_data', 'c')
      ->condition('status', CommentInterface::NOT_PUBLISHED, '=')
      ->condition('default_langcode', 1)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
