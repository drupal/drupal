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
   * Writes a list of grants to the database, deleting any previously saved ones.
   *
   * Modules that use node access can use this function when doing mass updates
   * due to widespread permission changes.
   *
   * Note: Don't call this function directly from a contributed module. Call
   * \Drupal\node\NodeAccessControlHandlerInterface::acquireGrants() instead.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose grants are being written.
   * @param $delete
   *   (optional) If false, does not delete records. This is only for optimization
   *   purposes, and assumes the caller has already performed a mass delete of
   *   some form. Defaults to TRUE.
   *
   * @deprecated in Drupal 8.x, will be removed before Drupal 9.0.
   *   Use \Drupal\node\NodeAccessControlHandlerInterface::acquireGrants().
   */
  public function writeGrants(NodeInterface $node, $delete = TRUE);

  /**
   * Creates the default node access grant entry on the grant storage.
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
