<?php

namespace Drupal\tracker\Controller;

use Drupal\comment\CommentStatisticsInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for tracker pages.
 */
class TrackerController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The database replica connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseReplica;

  /**
   * The comment statistics.
   *
   * @var \Drupal\comment\CommentStatisticsInterface
   */
  protected $commentStatistics;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a TrackerController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Database\Connection $databaseReplica
   *   The database replica connection.
   * @param \Drupal\comment\CommentStatisticsInterface $commentStatistics
   *   The comment statistics.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(Connection $database, Connection $databaseReplica, CommentStatisticsInterface $commentStatistics, DateFormatterInterface $dateFormatter, EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $database;
    $this->databaseReplica = $databaseReplica;
    $this->commentStatistics = $commentStatistics;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('database.replica'),
      $container->get('comment.statistics'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Title callback for the tracker.user_tab route.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return string
   *   The title.
   */
  public function getTitle(UserInterface $user) {
    return $user->getDisplayName();
  }

  /**
   * Checks access for the users recent content tracker page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being viewed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account viewing the page.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(UserInterface $user, AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated() && $user->id() == $account->id())
      ->cachePerUser();
  }

  /**
   * Builds content for the tracker controllers.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   (optional) The user account.
   *
   * @return array
   *   The render array.
   */
  public function buildContent(UserInterface $user = NULL) {
    if ($user) {
      $query = $this->database->select('tracker_user', 't')
        ->extend(PagerSelectExtender::class)
        ->addMetaData('base_table', 'tracker_user')
        ->condition('t.uid', $user->id());
    }
    else {
      $query = $this->databaseReplica->select('tracker_node', 't')
        ->extend(PagerSelectExtender::class)
        ->addMetaData('base_table', 'tracker_node');
    }

    // This array acts as a placeholder for the data selected later
    // while keeping the correct order.
    $tracker_data = $query
      ->addTag('node_access')
      ->fields('t', ['nid', 'changed'])
      ->condition('t.published', 1)
      ->orderBy('t.changed', 'DESC')
      ->limit(25)
      ->execute()
      ->fetchAllAssoc('nid');

    $cacheable_metadata = new CacheableMetadata();
    $rows = [];
    if (!empty($tracker_data)) {
      // Load nodes into an array with the same order as $tracker_data.
      /** @var \Drupal\node\NodeInterface[] $nodes */
      $nodes = $this->nodeStorage->loadMultiple(array_keys($tracker_data));

      // Enrich the node data.
      $result = $this->commentStatistics->read($nodes, 'node', FALSE);
      foreach ($result as $statistics) {
        // The node ID may not be unique; there can be multiple comment fields.
        // Make comment_count the total of all comments.
        $nid = $statistics->entity_id;
        if (empty($nodes[$nid]->comment_count)
          || !is_numeric($tracker_data[$nid]->comment_count)) {
          $tracker_data[$nid]->comment_count = $statistics->comment_count;
        }
        else {
          $tracker_data[$nid]->comment_count += $statistics->comment_count;
        }
        // Make the last comment timestamp reflect the latest comment.
        if (!isset($tracker_data[$nid]->last_comment_timestamp)) {
          $tracker_data[$nid]->last_comment_timestamp = $statistics->last_comment_timestamp;
        }
        else {
          $tracker_data[$nid]->last_comment_timestamp = max($tracker_data[$nid]->last_comment_timestamp, $statistics->last_comment_timestamp);
        }
      }

      // Display the data.
      foreach ($nodes as $node) {
        // Set the last activity time from tracker data. This also takes into
        // account comment activity, so getChangedTime() is not used.
        $last_activity = $tracker_data[$node->id()]->changed;

        $owner = $node->getOwner();
        $row = [
          'type' => node_get_type_label($node),
          'title' => [
            'data' => [
              '#type' => 'link',
              '#url' => $node->toUrl(),
              '#title' => $node->getTitle(),
            ],
            'data-history-node-id' => $node->id(),
            'data-history-node-timestamp' => $node->getChangedTime(),
          ],
          'author' => [
            'data' => [
              '#theme' => 'username',
              '#account' => $owner,
            ],
          ],
          'comments' => [
            'class' => ['comments'],
            'data' => $tracker_data[$node->id()]->comment_count ?? 0,
            'data-history-node-last-comment-timestamp' => $tracker_data[$node->id()]->last_comment_timestamp ?? 0,
          ],
          'last updated' => [
            'data' => t('@time ago', [
              '@time' => $this->dateFormatter->formatTimeDiffSince($last_activity),
            ]),
          ],
        ];

        $rows[] = $row;

        // Add node and node owner to cache tags.
        $cacheable_metadata->addCacheTags($node->getCacheTags());
        if ($owner) {
          $cacheable_metadata->addCacheTags($owner->getCacheTags());
        }
      }
    }

    // Add the list cache tag for nodes.
    $cacheable_metadata->addCacheTags($this->nodeStorage->getEntityType()->getListCacheTags());

    $page['tracker'] = [
      '#rows' => $rows,
      '#header' => [
        $this->t('Type'),
        $this->t('Title'),
        $this->t('Author'),
        $this->t('Comments'),
        $this->t('Last updated'),
      ],
      '#type' => 'table',
      '#empty' => $this->t('No content available.'),
    ];
    $page['pager'] = [
      '#type' => 'pager',
      '#weight' => 10,
    ];
    $page['#sorted'] = TRUE;
    $cacheable_metadata->addCacheContexts(['user.node_grants:view']);

    // Display the reading history if that module is enabled.
    if ($this->moduleHandler()->moduleExists('history')) {
      // Reading history is tracked for authenticated users only.
      if ($this->currentUser()->isAuthenticated()) {
        $page['#attached']['library'][] = 'tracker/history';
      }
      $cacheable_metadata->addCacheContexts(['user.roles:authenticated']);
    }
    $cacheable_metadata->applyTo($page);
    return $page;
  }

}
