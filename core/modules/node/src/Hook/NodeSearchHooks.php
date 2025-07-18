<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\search\SearchIndexInterface;

/**
 * Hook implementations for node entity operations.
 */
class NodeSearchHooks {

  public function __construct(
    protected readonly ?SearchIndexInterface $searchIndex = NULL,
  ) {}

  /**
   * Implements hook_node_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate($node): void {
    $this->reindexNodeForSearch($node->id());
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for comment entities.
   */
  #[Hook('comment_insert')]
  public function commentInsert($comment): void {
    // Reindex the node when comments are added.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      $this->reindexNodeForSearch($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for comment entities.
   */
  #[Hook('comment_update')]
  public function commentUpdate($comment): void {
    // Reindex the node when comments are changed.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      $this->reindexNodeForSearch($comment->getCommentedEntityId());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for comment entities.
   */
  #[Hook('comment_delete')]
  public function commentDelete($comment): void {
    // Reindex the node when comments are deleted.
    if ($comment->getCommentedEntityTypeId() == 'node') {
      $this->reindexNodeForSearch($comment->getCommentedEntityId());
    }
  }

  /**
   * Reindex a node for search.
   *
   * @param string|int $nid
   *   The node ID to reindex.
   */
  protected function reindexNodeForSearch(string|int $nid): void {
    // Reindex node context indexed by the node module search plugin.
    $this->searchIndex?->markForReindex('node_search', (int) $nid);
  }

}
