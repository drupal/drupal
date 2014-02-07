<?php

/**
 * @file
 * Contains \Drupal\node\NodeGrantStorageControllerInterface.
 */

namespace Drupal\node;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for node access controllers.
 */
interface NodeGrantDatabaseStorageInterface {

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
  public function checkAll(AccountInterface $account);

  /**
   * Alters a query when node access is required.
   *
   * @param mixed $query
   *   Query that is being altered.
   * @param array $tables
   *   A list of tables that need to be part of the alter.
   * @param string $op
   *    The operation to be performed on the node. Possible values are:
   *    - "view"
   *    - "update"
   *    - "delete"
   *    - "create"
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $base_table
   *   The base table of the query.
   *
   * @return int
   *   Status of the access check.
   */
  public function alterQuery($query, array $tables, $op, AccountInterface $account, $base_table);

  /**
   * Writes a list of grants to the database, deleting previously saved ones.
   *
   * If a realm is provided, it will only delete grants from that realm, but
   * it will always delete a grant from the 'all' realm. Modules that use
   * node access can use this method when doing mass updates due to widespread
   * permission changes.
   *
   * Note: Don't call this method directly from a contributed module. Call
   * node_access_write_grants() instead.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose grants are being written.
   * @param array $grants
   *   A list of grants to write. Each grant is an array that must contain the
   *   following keys: realm, gid, grant_view, grant_update, grant_delete.
   *   The realm is specified by a particular module; the gid is as well, and
   *   is a module-defined id to define grant privileges. each grant_* field
   *   is a boolean value.
   * @param string $realm
   *   (optional) If provided, read/write grants for that realm only. Defaults to
   *   NULL.
   * @param bool $delete
   *   (optional) If false, does not delete records. This is only for optimization
   *   purposes, and assumes the caller has already performed a mass delete of
   *   some form. Defaults to TRUE.
   *
   * @see node_access_write_grants()
   * @see node_access_acquire_grants()
   */
  public function write(NodeInterface $node, array $grants, $realm = NULL, $delete = TRUE);

  /**
   * Deletes all node access entries.
   */
  public function delete();

  /**
   * Creates the default node access grant entry.
   */
  public function writeDefault();

  /**
   * Determines access to nodes based on node grants.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity for which to check 'create' access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'edit', 'create' or
   *   'delete'.
   * @param string $langcode
   *   The language code for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return bool|null
   *   TRUE if access was granted, FALSE if access was denied or NULL if no
   *   module implements hook_node_grants(), the node does not (yet) have an id
   *   or none of the implementing modules explicitly granted or denied access.
   */
  public function access(NodeInterface $node, $operation, $langcode, AccountInterface $account);

  /**
   * Counts available node grants.
   *
   * @return int
   *   Returns the amount of node grants.
   */
  public function count();

  /**
   * Remove the access records belonging to certain nodes.
   *
   * @param array $nids
   *   A list of node IDs. The grant records belonging to these nodes will be
   *   deleted.
   */
  public function deleteNodeRecords(array $nids);

}
