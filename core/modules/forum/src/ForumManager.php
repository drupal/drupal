<?php

/**
 * @file
 * Contains \Drupal\forum\ForumManager.
 */

namespace Drupal\forum;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\comment\CommentManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides forum manager service.
 */
class ForumManager implements ForumManagerInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait {
    __wakeup as defaultWakeup;
    __sleep as defaultSleep;
  }

  /**
   * Forum sort order, newest first.
   */
  const NEWEST_FIRST = 1;

  /**
   * Forum sort order, oldest first.
   */
  const OLDEST_FIRST = 2;

  /**
   * Forum sort order, posts with most comments first.
   */
  const MOST_POPULAR_FIRST = 3;

  /**
   * Forum sort order, posts with the least comments first.
   */
  const LEAST_POPULAR_FIRST = 4;

  /**
   * Forum settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity manager service
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Database connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * Array of last post information keyed by forum (term) id.
   *
   * @var array
   */
  protected $lastPostData = array();

  /**
   * Array of forum statistics keyed by forum (term) id.
   *
   * @var array
   */
  protected $forumStatistics = array();

  /**
   * Array of forum children keyed by parent forum (term) id.
   *
   * @var array
   */
  protected $forumChildren = array();

  /**
   * Array of history keyed by nid.
   *
   * @var array
   */
  protected $history = array();

  /**
   * Cached forum index.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $index;

  /**
   * Constructs the forum manager service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager service.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, Connection $connection, TranslationInterface $string_translation, CommentManagerInterface $comment_manager) {
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->connection = $connection;
    $this->stringTranslation = $string_translation;
    $this->commentManager = $comment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopics($tid, AccountInterface $account) {
    $config = $this->configFactory->get('forum.settings');
    $forum_per_page = $config->get('topics.page_limit');
    $sortby = $config->get('topics.order');

    $header = array(
      array('data' => $this->t('Topic'), 'field' => 'f.title'),
      array('data' => $this->t('Replies'), 'field' => 'f.comment_count'),
      array('data' => $this->t('Last reply'), 'field' => 'f.last_comment_timestamp'),
    );

    $order = $this->getTopicOrder($sortby);
    for ($i = 0; $i < count($header); $i++) {
      if ($header[$i]['field'] == $order['field']) {
        $header[$i]['sort'] = $order['sort'];
      }
    }

    $query = $this->connection->select('forum_index', 'f')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('f');
    $query
      ->condition('f.tid', $tid)
      ->addTag('node_access')
      ->addMetaData('base_table', 'forum_index')
      ->orderBy('f.sticky', 'DESC')
      ->orderByHeader($header)
      ->limit($forum_per_page);

    $count_query = $this->connection->select('forum_index', 'f');
    $count_query->condition('f.tid', $tid);
    $count_query->addExpression('COUNT(*)');
    $count_query->addTag('node_access');
    $count_query->addMetaData('base_table', 'forum_index');

    $query->setCountQuery($count_query);
    $result = $query->execute();
    $nids = array();
    foreach ($result as $record) {
      $nids[] = $record->nid;
    }
    if ($nids) {
      $nodes = $this->entityManager->getStorage('node')->loadMultiple($nids);

      $query = $this->connection->select('node_field_data', 'n')
        ->extend('Drupal\Core\Database\Query\TableSortExtender');
      $query->fields('n', array('nid'));

      $query->join('comment_entity_statistics', 'ces', "n.nid = ces.entity_id AND ces.field_name = 'comment_forum' AND ces.entity_type = 'node'");
      $query->fields('ces', array(
        'cid',
        'last_comment_uid',
        'last_comment_timestamp',
        'comment_count'
      ));

      $query->join('forum_index', 'f', 'f.nid = n.nid');
      $query->addField('f', 'tid', 'forum_tid');

      $query->join('users', 'u', 'n.uid = u.uid');
      $query->addField('u', 'name');

      $query->join('users', 'u2', 'ces.last_comment_uid = u2.uid');

      $query->addExpression('CASE ces.last_comment_uid WHEN 0 THEN ces.last_comment_name ELSE u2.name END', 'last_comment_name');

      $query
        ->orderBy('f.sticky', 'DESC')
        ->orderByHeader($header)
        ->condition('n.nid', $nids)
        // @todo This should be actually filtering on the desired node language
        //   and just fall back to the default language.
        ->condition('n.default_langcode', 1);

      $result = array();
      foreach ($query->execute() as $row) {
        $topic = $nodes[$row->nid];
        $topic->comment_mode = $topic->comment_forum->status;

        foreach ($row as $key => $value) {
          $topic->{$key} = $value;
        }
        $result[] = $topic;
      }
    }
    else {
      $result = array();
    }

    $topics = array();
    $first_new_found = FALSE;
    foreach ($result as $topic) {
      if ($account->isAuthenticated()) {
        // A forum is new if the topic is new, or if there are new comments since
        // the user's last visit.
        if ($topic->forum_tid != $tid) {
          $topic->new = 0;
        }
        else {
          $history = $this->lastVisit($topic->id(), $account);
          $topic->new_replies = $this->commentManager->getCountNewComments($topic, 'comment_forum', $history);
          $topic->new = $topic->new_replies || ($topic->last_comment_timestamp > $history);
        }
      }
      else {
        // Do not track "new replies" status for topics if the user is anonymous.
        $topic->new_replies = 0;
        $topic->new = 0;
      }

      // Make sure only one topic is indicated as the first new topic.
      $topic->first_new = FALSE;
      if ($topic->new != 0 && !$first_new_found) {
        $topic->first_new = TRUE;
        $first_new_found = TRUE;
      }

      if ($topic->comment_count > 0) {
        $last_reply = new \stdClass();
        $last_reply->created = $topic->last_comment_timestamp;
        $last_reply->name = $topic->last_comment_name;
        $last_reply->uid = $topic->last_comment_uid;
        $topic->last_reply = $last_reply;
      }
      $topics[$topic->id()] = $topic;
    }

    return array('topics' => $topics, 'header' => $header);

  }

  /**
   * Gets topic sorting information based on an integer code.
   *
   * @param int $sortby
   *   One of the following integers indicating the sort criteria:
   *   - ForumManager::NEWEST_FIRST: Date - newest first.
   *   - ForumManager::OLDEST_FIRST: Date - oldest first.
   *   - ForumManager::MOST_POPULAR_FIRST: Posts with the most comments first.
   *   - ForumManager::LEAST_POPULAR_FIRST: Posts with the least comments first.
   *
   * @return array
   *   An array with the following values:
   *   - field: A field for an SQL query.
   *   - sort: 'asc' or 'desc'.
   */
  protected function getTopicOrder($sortby) {
    switch ($sortby) {
      case static::NEWEST_FIRST:
        return array('field' => 'f.last_comment_timestamp', 'sort' => 'desc');

      case static::OLDEST_FIRST:
        return array('field' => 'f.last_comment_timestamp', 'sort' => 'asc');

      case static::MOST_POPULAR_FIRST:
        return array('field' => 'f.comment_count', 'sort' => 'desc');

      case static::LEAST_POPULAR_FIRST:
        return array('field' => 'f.comment_count', 'sort' => 'asc');

    }
  }

  /**
   * Gets the last time the user viewed a node.
   *
   * @param int $nid
   *   The node ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to fetch last time for.
   *
   * @return int
   *   The timestamp when the user last viewed this node, if the user has
   *   previously viewed the node; otherwise HISTORY_READ_LIMIT.
   */
  protected function lastVisit($nid, AccountInterface $account) {
    if (empty($this->history[$nid])) {
      $result = $this->connection->select('history', 'h')
        ->fields('h', array('nid', 'timestamp'))
        ->condition('uid', $account->id())
        ->execute();
      foreach ($result as $t) {
        $this->history[$t->nid] = $t->timestamp > HISTORY_READ_LIMIT ? $t->timestamp : HISTORY_READ_LIMIT;
      }
    }
    return isset($this->history[$nid]) ? $this->history[$nid] : HISTORY_READ_LIMIT;
  }

  /**
   * Provides the last post information for the given forum tid.
   *
   * @param int $tid
   *   The forum tid.
   *
   * @return \stdClass
   *   The last post for the given forum.
   */
  protected function getLastPost($tid) {
    if (!empty($this->lastPostData[$tid])) {
      return $this->lastPostData[$tid];
    }
    // Query "Last Post" information for this forum.
    $query = $this->connection->select('node_field_data', 'n');
    $query->join('forum', 'f', 'n.vid = f.vid AND f.tid = :tid', array(':tid' => $tid));
    $query->join('comment_entity_statistics', 'ces', "n.nid = ces.entity_id AND ces.field_name = 'comment_forum' AND ces.entity_type = 'node'");
    $query->join('users', 'u', 'ces.last_comment_uid = u.uid');
    $query->addExpression('CASE ces.last_comment_uid WHEN 0 THEN ces.last_comment_name ELSE u.name END', 'last_comment_name');

    $topic = $query
      ->fields('ces', array('last_comment_timestamp', 'last_comment_uid'))
      ->condition('n.status', 1)
      ->orderBy('last_comment_timestamp', 'DESC')
      ->range(0, 1)
      ->addTag('node_access')
      ->execute()
      ->fetchObject();

    // Build the last post information.
    $last_post = new \stdClass();
    if (!empty($topic->last_comment_timestamp)) {
      $last_post->created = $topic->last_comment_timestamp;
      $last_post->name = $topic->last_comment_name;
      $last_post->uid = $topic->last_comment_uid;
    }

    $this->lastPostData[$tid] = $last_post;
    return $last_post;
  }

  /**
   * Provides statistics for a forum.
   *
   * @param int $tid
   *   The forum tid.
   *
   * @return \stdClass|null
   *   Statistics for the given forum if statistics exist, else NULL.
   */
  protected function getForumStatistics($tid) {
    if (empty($this->forumStatistics)) {
      // Prime the statistics.
      $query = $this->connection->select('node_field_data', 'n');
      $query->join('comment_entity_statistics', 'ces', "n.nid = ces.entity_id AND ces.field_name = 'comment_forum' AND ces.entity_type = 'node'");
      $query->join('forum', 'f', 'n.vid = f.vid');
      $query->addExpression('COUNT(n.nid)', 'topic_count');
      $query->addExpression('SUM(ces.comment_count)', 'comment_count');
      $this->forumStatistics = $query
        ->fields('f', array('tid'))
        ->condition('n.status', 1)
        ->condition('n.default_langcode', 1)
        ->groupBy('tid')
        ->addTag('node_access')
        ->execute()
        ->fetchAllAssoc('tid');
    }

    if (!empty($this->forumStatistics[$tid])) {
      return $this->forumStatistics[$tid];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren($vid, $tid) {
    if (!empty($this->forumChildren[$tid])) {
      return $this->forumChildren[$tid];
    }
    $forums = array();
    $_forums = taxonomy_get_tree($vid, $tid, NULL, TRUE);
    foreach ($_forums as $forum) {
      // Merge in the topic and post counters.
      if (($count = $this->getForumStatistics($forum->id()))) {
        $forum->num_topics = $count->topic_count;
        $forum->num_posts = $count->topic_count + $count->comment_count;
      }
      else {
        $forum->num_topics = 0;
        $forum->num_posts = 0;
      }

      // Merge in last post details.
      $forum->last_post = $this->getLastPost($forum->id());
      $forums[$forum->id()] = $forum;
    }

    $this->forumChildren[$tid] = $forums;
    return $forums;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    if ($this->index) {
      return $this->index;
    }

    $vid = $this->configFactory->get('forum.settings')->get('vocabulary');
    $index = $this->entityManager->getStorage('taxonomy_term')->create(array(
      'tid' => 0,
      'container' => 1,
      'parents' => array(),
      'isIndex' => TRUE,
      'vid' => $vid
    ));

    // Load the tree below.
    $index->forums = $this->getChildren($vid, 0);
    $this->index = $index;
    return $index;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    // Reset the index.
    $this->index = NULL;
    // Reset history.
    $this->history = array();
  }

  /**
   * {@inheritdoc}
   */
  public function getParents($tid) {
    return taxonomy_term_load_parents_all($tid);
  }

  /**
   * {@inheritdoc}
   */
  public function checkNodeType(NodeInterface $node) {
    // Fetch information about the forum field.
    $field_definitions = $this->entityManager->getFieldDefinitions('node', $node->bundle());
    return !empty($field_definitions['taxonomy_forums']);
  }

  /**
   * {@inheritdoc}
   */
  public function unreadTopics($term, $uid) {
    $query = $this->connection->select('node_field_data', 'n');
    $query->join('forum', 'f', 'n.vid = f.vid AND f.tid = :tid', array(':tid' => $term));
    $query->leftJoin('history', 'h', 'n.nid = h.nid AND h.uid = :uid', array(':uid' => $uid));
    $query->addExpression('COUNT(n.nid)', 'count');
    return $query
      ->condition('status', 1)
      // @todo This should be actually filtering on the desired node status
      //   field language and just fall back to the default language.
      ->condition('n.default_langcode', 1)
      ->condition('n.created', HISTORY_READ_LIMIT, '>')
      ->isNull('h.nid')
      ->addTag('node_access')
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = $this->defaultSleep();
    // Do not serialize static cache.
    unset($vars['history'], $vars['index'], $vars['lastPostData'], $vars['forumChildren'], $vars['forumStatistics']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    $this->defaultWakeup();
    // Initialize static cache.
    $this->history = array();
    $this->lastPostData = array();
    $this->forumChildren = array();
    $this->forumStatistics = array();
    $this->index = NULL;
  }

}
