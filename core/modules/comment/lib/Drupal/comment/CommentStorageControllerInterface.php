<?php

/**
 * @file
 * Contains \Drupal\comment\CommentStorageControllerInterface.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for comment entity controller classes.
 */
interface CommentStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Get the maximum encoded thread value for the top level comments.
   *
   * @param EntityInterface $comment
   *   A comment entity.
   *
   * @return string
   *   The maximum encoded thread value among the top level comments of the
   *   node $comment belongs to.
   */
  public function getMaxThread(EntityInterface $comment);

  /**
   * Get the maximum encoded thread value for the children of this comment.
   *
   * @param EntityInterface $comment
   *   A comment entity.
   *
   * @return string
   *   The maximum encoded thread value among all replies of $comment.
   */
  public function getMaxThreadPerThread(EntityInterface $comment);

  /**
   * Gets the comment ids of the passed comment entities' children.
   *
   * @param array $comments
   *   An array of comment entities keyed by their ids.
   * @return array
   *   The entity ids of the passed comment entities' children as an array.
   */
  public function getChildCids(array $comments);

  /**
   * Updates the comment statistics for a given node.
   *
   * The {node_comment_statistics} table has the following fields:
   * - last_comment_timestamp: The timestamp of the last comment for this node,
   *   or the node created timestamp if no comments exist for the node.
   * - last_comment_name: The name of the anonymous poster for the last comment.
   * - last_comment_uid: The user ID of the poster for the last comment for
   *   this node, or the node author's user ID if no comments exist for the
   *   node.
   * - comment_count: The total number of approved/published comments on this
   *   node.
   *
   * @param $nid
   *   The node ID.
   */
  public function updateNodeStatistics($nid);

}
