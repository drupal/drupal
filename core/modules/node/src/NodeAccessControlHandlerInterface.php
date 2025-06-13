<?php

namespace Drupal\node;

use Drupal\Core\Session\AccountInterface;

/**
 * Node specific entity access control methods.
 *
 * @ingroup node_access
 */
interface NodeAccessControlHandlerInterface {

  /**
   * Gets the list of node access grants.
   *
   * This function is called to check the access grants for a node. It collects
   * all node access grants for the node from hook_node_access_records()
   * implementations, allows these grants to be altered via
   * hook_node_access_records_alter() implementations, and returns the grants to
   * the caller.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The $node to acquire grants for.
   *
   * @return array
   *   The access rules for the node.
   */
  public function acquireGrants(NodeInterface $node);

  /**
   * Creates the default node access grant entry on the grant storage.
   *
   * @see \Drupal\node\NodeGrantDatabaseStorageInterface::writeDefault()
   */
  public function writeDefaultGrant();

  /**
   * Deletes all node access entries.
   */
  public function deleteGrants();

  /**
   * Counts available node grants.
   *
   * @return int
   *   Returns the amount of node grants.
   */
  public function countGrants();

  /**
   * Checks all grants for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   *
   * @return int
   *   Status of the access check.
   */
  public function checkAllGrants(AccountInterface $account);

}
