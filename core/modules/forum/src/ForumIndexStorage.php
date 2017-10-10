<?php

namespace Drupal\forum;

use Drupal\comment\CommentInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\NodeInterface;

/**
 * Handles CRUD operations to {forum_index} table.
 */
class ForumIndexStorage implements ForumIndexStorageInterface {

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ForumIndexStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalTermId(NodeInterface $node) {
    return $this->database->queryRange("SELECT f.tid FROM {forum} f INNER JOIN {node} n ON f.vid = n.vid WHERE n.nid = :nid ORDER BY f.vid DESC", 0, 1, [':nid' => $node->id()])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function create(NodeInterface $node) {
    $this->database->insert('forum')
      ->fields([
        'tid' => $node->forum_tid,
        'vid' => $node->getRevisionId(),
        'nid' => $node->id(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function read(array $vids) {
    return $this->database->select('forum', 'f')
      ->fields('f', ['nid', 'tid'])
      ->condition('f.vid', $vids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(NodeInterface $node) {
    $this->database->delete('forum')
      ->condition('nid', $node->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision(NodeInterface $node) {
    $this->database->delete('forum')
      ->condition('nid', $node->id())
      ->condition('vid', $node->getRevisionId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function update(NodeInterface $node) {
    $this->database->update('forum')
      ->fields(['tid' => $node->forum_tid])
      ->condition('vid', $node->getRevisionId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(NodeInterface $node) {
    $nid = $node->id();
    $count = $this->database->query("SELECT COUNT(cid) FROM {comment_field_data} c INNER JOIN {forum_index} i ON c.entity_id = i.nid WHERE c.entity_id = :nid AND c.field_name = 'comment_forum' AND c.entity_type = 'node' AND c.status = :status AND c.default_langcode = 1", [
      ':nid' => $nid,
      ':status' => CommentInterface::PUBLISHED,
    ])->fetchField();

    if ($count > 0) {
      // Comments exist.
      $last_reply = $this->database->queryRange("SELECT cid, name, created, uid FROM {comment_field_data} WHERE entity_id = :nid AND field_name = 'comment_forum' AND entity_type = 'node' AND status = :status AND default_langcode = 1 ORDER BY cid DESC", 0, 1, [
        ':nid' => $nid,
        ':status' => CommentInterface::PUBLISHED,
      ])->fetchObject();
      $this->database->update('forum_index')
        ->fields([
          'comment_count' => $count,
          'last_comment_timestamp' => $last_reply->created,
        ])
        ->condition('nid', $nid)
        ->execute();
    }
    else {
      // Comments do not exist.
      // @todo This should be actually filtering on the desired node language
      $this->database->update('forum_index')
        ->fields([
          'comment_count' => 0,
          'last_comment_timestamp' => $node->getCreatedTime(),
        ])
        ->condition('nid', $nid)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createIndex(NodeInterface $node) {
    $query = $this->database->insert('forum_index')
      ->fields(['nid', 'title', 'tid', 'sticky', 'created', 'comment_count', 'last_comment_timestamp']);
    foreach ($node->getTranslationLanguages() as $langcode => $language) {
      $translation = $node->getTranslation($langcode);
      foreach ($translation->taxonomy_forums as $item) {
        $query->values([
          'nid' => $node->id(),
          'title' => $translation->label(),
          'tid' => $item->target_id,
          'sticky' => (int) $node->isSticky(),
          'created' => $node->getCreatedTime(),
          'comment_count' => 0,
          'last_comment_timestamp' => $node->getCreatedTime(),
        ]);
      }
    }
    $query->execute();
    // The logic for determining last_comment_count is fairly complex, so
    // update the index too.
    if ($node->isNew()) {
      $this->updateIndex($node);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteIndex(NodeInterface $node) {
    $this->database->delete('forum_index')
      ->condition('nid', $node->id())
      ->execute();
  }

}
