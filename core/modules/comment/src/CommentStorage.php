<?php

/**
 * @file
 * Definition of Drupal\comment\CommentStorage.
 */

namespace Drupal\comment;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the controller class for comments.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for comment entities.
 */
class CommentStorage extends SqlContentEntityStorage implements CommentStorageInterface {

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, AccountInterface $current_user, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);
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
      $container->get('current_user'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
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
  public function getNewCommentPageNumber($total_comments, $new_comments, FieldableEntityInterface $entity, $field_name = 'comment') {
    $field = $entity->getFieldDefinition($field_name);
    $comments_per_page = $field->getSetting('per_page');

    if ($total_comments <= $comments_per_page) {
      // Only one page of comments.
      $count = 0;
    }
    elseif ($field->getSetting('default_mode') == CommentManagerInterface::COMMENT_MODE_FLAT) {
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
   *
   * To display threaded comments in the correct order we keep a 'thread' field
   * and order by that value. This field keeps this data in
   * a way which is easy to update and convenient to use.
   *
   * A "thread" value starts at "1". If we add a child (A) to this comment,
   * we assign it a "thread" = "1.1". A child of (A) will have "1.1.1". Next
   * brother of (A) will get "1.2". Next brother of the parent of (A) will get
   * "2" and so on.
   *
   * First of all note that the thread field stores the depth of the comment:
   * depth 0 will be "X", depth 1 "X.X", depth 2 "X.X.X", etc.
   *
   * Now to get the ordering right, consider this example:
   *
   * 1
   * 1.1
   * 1.1.1
   * 1.2
   * 2
   *
   * If we "ORDER BY thread ASC" we get the above result, and this is the
   * natural order sorted by time. However, if we "ORDER BY thread DESC"
   * we get:
   *
   * 2
   * 1.2
   * 1.1.1
   * 1.1
   * 1
   *
   * Clearly, this is not a natural way to see a thread, and users will get
   * confused. The natural order to show a thread by time desc would be:
   *
   * 2
   * 1
   * 1.2
   * 1.1
   * 1.1.1
   *
   * which is what we already did before the standard pager patch. To achieve
   * this we simply add a "/" at the end of each "thread" value. This way, the
   * thread fields will look like this:
   *
   * 1/
   * 1.1/
   * 1.1.1/
   * 1.2/
   * 2/
   *
   * we add "/" since this char is, in ASCII, higher than every number, so if
   * now we "ORDER BY thread DESC" we get the correct order. However this would
   * spoil the reverse ordering, "ORDER BY thread ASC" -- here, we do not need
   * to consider the trailing "/" so we use a substring only.
   */
  public function loadThread(EntityInterface $entity, $field_name, $mode, $comments_per_page = 0, $pager_id = 0) {
    $query = $this->database->select('comment_field_data', 'c');
    if ($comments_per_page) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($comments_per_page);
      if ($pager_id) {
        $query->element($pager_id);
      }
    }
    $query->addField('c', 'cid');
    $query
      ->condition('c.entity_id', $entity->id())
      ->condition('c.entity_type', $entity->getEntityTypeId())
      ->condition('c.field_name', $field_name)
      ->condition('c.default_langcode', 1)
      ->addTag('entity_access')
      ->addTag('comment_filter')
      ->addMetaData('base_table', 'comment')
      ->addMetaData('entity', $entity)
      ->addMetaData('field_name', $field_name);

    $count_query = $this->database->select('comment_field_data', 'c');
    $count_query->addExpression('COUNT(*)');
    $count_query
      ->condition('c.entity_id', $entity->id())
      ->condition('c.entity_type', $entity->getEntityTypeId())
      ->condition('c.field_name', $field_name)
      ->condition('c.default_langcode', 1)
      ->addTag('entity_access')
      ->addTag('comment_filter')
      ->addMetaData('base_table', 'comment')
      ->addMetaData('entity', $entity)
      ->addMetaData('field_name', $field_name);

    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('c.status', CommentInterface::PUBLISHED);
      $count_query->condition('c.status', CommentInterface::PUBLISHED);
    }
    if ($mode == CommentManagerInterface::COMMENT_MODE_FLAT) {
      $query->orderBy('c.cid', 'ASC');
    }
    else {
      // See comment above. Analysis reveals that this doesn't cost too
      // much. It scales much much better than having the whole comment
      // structure.
      $query->addExpression('SUBSTRING(c.thread, 1, (LENGTH(c.thread) - 1))', 'torder');
      $query->orderBy('torder', 'ASC');
    }

    $query->setCountQuery($count_query);
    $cids = $query->execute()->fetchCol();

    $comments = array();
    if ($cids) {
      $comments = $this->loadMultiple($cids);
    }

    return $comments;
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
