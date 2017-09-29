<?php

namespace Drupal\Tests\migrate\Kernel;

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
