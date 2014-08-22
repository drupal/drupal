<?php

/**
 * @file
 * Contains \Drupal\node\NodeStorageControllerInterface.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for node entity storage classes.
 */
interface NodeStorageInterface extends EntityStorageInterface {

  /**
   * Returns a list of node revision IDs for a specific node.
   *
   * @param \Drupal\node\NodeInterface
   *   The node entity.
   *
   * @return int[]
   *   Node revision IDs (in ascending order).
   */
  public function revisionIds(NodeInterface $node);

  /**
   * Returns a list of revision IDs having a given user as node author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Node revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Updates all nodes of one type to be of another type.
   *
   * @param string $old_type
   *   The current node type of the nodes.
   * @param string $new_type
   *   The new node type of the nodes.
   *
   * @return int
   *   The number of nodes whose node type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all nodes with the given language.
   *
   * @param $language
   *  The language object.
   */
  public function clearRevisionsLanguage($language);
}
