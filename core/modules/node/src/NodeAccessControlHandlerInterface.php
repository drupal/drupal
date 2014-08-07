<?php
/**
 * @file
 * Contains \Drupal\node\NodeAccessControlHandlerInterface.
 */

namespace Drupal\node;

use Drupal\Core\Session\AccountInterface;

/**
 * Node specific entity access control methods.
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
   * @return array $grants
   *   The access rules for the node.
   */
  public function acquireGrants(NodeInterface $node);


  /**
   * Writes a list of grants to the database, deleting any previously saved ones.
   *
   * If a realm is provided, it will only delete grants from that realm, but it
   * will always delete a grant from the 'all' realm. Modules that utilize
   * node access can use this function when doing mass updates due to widespread
   * permission changes.
   *
   * Note: Don't call this function directly from a contributed module. Call
   * node_access_acquire_grants() instead.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose grants are being written.
   * @param $grants
   *   A list of grants to write. See hook_node_access_records() for the
   *   expected structure of the grants array.
   * @param $realm
   *   (optional) If provided, read/write grants for that realm only. Defaults to
   *   NULL.
   * @param $delete
   *   (optional) If false, does not delete records. This is only for optimization
   *   purposes, and assumes the caller has already performed a mass delete of
   *   some form. Defaults to TRUE.
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
   * @return int.
   *   Status of the access check.
   */
  public function checkAllGrants(AccountInterface $account);

}
