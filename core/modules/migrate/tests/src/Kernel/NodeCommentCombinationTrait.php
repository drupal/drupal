<?php

namespace Drupal\Tests\migrate\Kernel;

/**
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase::migrateFields()
 *   instead.
 */

use Drupal\comment\Entity\CommentType;
use Drupal\node\Entity\NodeType;

/**
 * Provides methods for testing node and comment combinations.
 */
trait NodeCommentCombinationTrait {

  /**
   * Creates a node type with a corresponding comment type.
   *
   * @param string $node_type
   *   The node type ID.
   * @param string $comment_type
   *   (optional) The comment type ID, if not provided defaults to
   *   comment_node_{type}.
   */
  protected function createNodeCommentCombination($node_type, $comment_type = NULL) {
    @trigger_error('NodeCommentCombinationTrait::createNodeCommentCombination() is deprecated in Drupal 8.7.x, will be removed before Drupal 9.0.0. Use \Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase::migrateFields() instead.', E_USER_DEPRECATED);

    if (!$comment_type) {
      $comment_type = "comment_node_$node_type";
    }
    NodeType::create([
      'type' => $node_type,
      'label' => $this->randomString(),
    ])->save();

    CommentType::create([
      'id' => $comment_type,
      'label' => $this->randomString(),
      'target_entity_type_id' => 'node',
    ])->save();
  }

}
