<?php

namespace Drupal\tracker;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentStatisticsInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the storage class for tracker data.
 */
class TrackerStorage implements TrackerStorageInterface {

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The comment statistics.
   *
   * @var \Drupal\comment\CommentStatisticsInterface
   */
  protected $commentStatistics;

  /**
   * Constructs a new TrackerStorage class.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing tracker data.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\comment\CommentStatisticsInterface $comment_statistics
   *   The comment statistics.
   */
  public function __construct(StateInterface $state, Connection $connection, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config, LoggerChannelFactoryInterface $logger_factory, CommentStatisticsInterface $comment_statistics) {
    $this->state = $state;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config;
    $this->loggerFactory = $logger_factory;
    $this->commentStatistics = $comment_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public function add($nid, $uid, $changed) {
    // @todo This should be actually filtering on the desired language and just
    //   fall back to the default language.
    $node = $this->connection->query('SELECT [nid], [status], [uid], [changed] FROM {node_field_data} WHERE [nid] = :nid AND [default_langcode] = 1 ORDER BY [changed] DESC, [status] DESC', [':nid' => $nid])->fetchObject();

    // Adding a comment can only increase the changed timestamp, so our
    // calculation here is simple.
    $changed = max($node->changed, $changed);

    // Update the node-level data.
    $this->connection->merge('tracker_node')
      ->key('nid', $nid)
      ->fields([
        'changed' => $changed,
        'published' => $node->status,
      ])
      ->execute();

    // Create or update the user-level data, first for the user posting.
    $this->connection->merge('tracker_user')
      ->keys([
        'nid' => $nid,
        'uid' => $uid,
      ])
      ->fields([
        'changed' => $changed,
        'published' => $node->status,
      ])
      ->execute();
    // Update the times for all the other users tracking the post.
    $this->connection->update('tracker_user')
      ->condition('nid', $nid)
      ->fields([
        'changed' => $changed,
        'published' => $node->status,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function remove($nid, $uid = NULL, $changed = NULL) {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    // The user only keeps their subscription if the node exists.
    if ($node) {
      // And they are the author of the node.
      $keep_subscription = ($node->getOwnerId() == $uid);

      // Or if they have commented on the node.
      if (!$keep_subscription) {
        // Check if the user has commented at least once on the given nid.
        $keep_subscription = $this->entityTypeManager->getStorage('comment')->getQuery('AND')
          ->accessCheck(FALSE)
          ->condition('entity_type', 'node')
          ->condition('entity_id', $nid)
          ->condition('uid', $uid)
          ->condition('status', CommentInterface::PUBLISHED)
          ->range(0, 1)
          ->count()
          ->execute();
      }

      // If we haven't found a reason to keep the user's subscription, delete it.
      if (!$keep_subscription) {
        $this->connection->delete('tracker_user')
          ->condition('nid', $nid)
          ->condition('uid', $uid)
          ->execute();
      }

      // Now we need to update the (possibly) changed timestamps for other users
      // and the node itself.
      // We only need to do this if the removed item has a timestamp that equals
      // or exceeds the listed changed timestamp for the node.
      $tracker_node = $this->connection->query('SELECT [nid], [changed] FROM {tracker_node} WHERE [nid] = :nid', [':nid' => $nid])->fetchObject();
      if ($tracker_node && $changed >= $tracker_node->changed) {
        // If we're here, the item being removed is *possibly* the item that
        // established the node's changed timestamp.

        // We just have to recalculate things from scratch.
        $changed = $this->calculateChanged($node);

        // And then we push the out the new changed timestamp to our denormalized
        // tables.
        $this->connection->update('tracker_node')
          ->fields([
            'changed' => $changed,
            'published' => $node->isPublished(),
          ])
          ->condition('nid', $nid)
          ->execute();
        $this->connection->update('tracker_node')
          ->fields([
            'changed' => $changed,
            'published' => $node->isPublished(),
          ])
          ->condition('nid', $nid)
          ->execute();
      }
    }
    else {
      // If the node doesn't exist, remove everything.
      $this->connection->delete('tracker_node')
        ->condition('nid', $nid)
        ->execute();
      $this->connection->delete('tracker_user')
        ->condition('nid', $nid)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeNode(NodeInterface $node) {
    $this->connection->delete('tracker_node')
      ->condition('nid', $node->id())
      ->execute();
    $this->connection->delete('tracker_user')
      ->condition('nid', $node->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateChanged($node) {
    $changed = $node->getChangedTime();
    $latest_comment = $this->commentStatistics->read([$node], 'node', FALSE);
    if ($latest_comment && $latest_comment->last_comment_timestamp > $changed) {
      $changed = $latest_comment->last_comment_timestamp;
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function updateAll() {
    $max_nid = $this->state->get('tracker.index_nid') ?: 0;
    if ($max_nid > 0) {
      $last_nid = FALSE;
      $count = 0;

      $nids = $this->entityTypeManager->getStorage('node')->getQuery('AND')
        ->accessCheck(FALSE)
        ->condition('nid', $max_nid, '<=')
        ->sort('nid', 'DESC')
        ->range(0, $this->configFactory->get('tracker.settings')->get('cron_index_limit'))
        ->execute();

      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      foreach ($nodes as $nid => $node) {

        // Calculate the changed timestamp for this node.
        $changed = $this->calculateChanged($node);

        // Remove existing data for this node.
        $this->connection->delete('tracker_node')
          ->condition('nid', $nid)
          ->execute();
        $this->connection->delete('tracker_user')
          ->condition('nid', $nid)
          ->execute();

        // Insert the node-level data.
        $this->connection->insert('tracker_node')
          ->fields([
            'nid' => $nid,
            'published' => (int) $node->isPublished(),
            'changed' => $changed,
          ])
          ->execute();

        // Insert the user-level data for the node's author.
        $this->connection->insert('tracker_user')
          ->fields([
            'nid' => $nid,
            'published' => (int) $node->isPublished(),
            'changed' => $changed,
            'uid' => $node->getOwnerId(),
          ])
          ->execute();

        // Insert the user-level data for the commenters (except if a commenter
        // is the node's author).

        // Get unique user IDs via entityQueryAggregate because it's the easiest
        // database agnostic way. We don't actually care about the comments here
        // so don't add an aggregate field.
        $result = $this->entityTypeManager->getStorage('comment')->getAggregateQuery('AND')
          ->accessCheck(FALSE)
          ->condition('entity_type', 'node')
          ->condition('entity_id', $node->id())
          ->condition('uid', $node->getOwnerId(), '<>')
          ->condition('status', CommentInterface::PUBLISHED)
          ->groupBy('uid')
          ->execute();
        if ($result) {
          $query = $this->connection->insert('tracker_user');
          foreach ($result as $row) {
            $query->fields([
              'uid' => $row['uid'],
              'nid' => $nid,
              'published' => CommentInterface::PUBLISHED,
              'changed' => $changed,
            ]);
          }
          $query->execute();
        }

        // Note that we have indexed at least one node.
        $last_nid = $nid;

        $count++;
      }

      if ($last_nid !== FALSE) {
        // Prepare a starting point for the next run.
        $this->state->set('tracker.index_nid', $last_nid - 1);

        $this->loggerFactory->get('tracker')->notice('Indexed %count content items for tracking.', ['%count' => $count]);
      }
      else {
        // If all nodes have been indexed, set to zero to skip future cron runs.
        $this->state->set('tracker.index_nid', 0);
      }
    }
  }

}
